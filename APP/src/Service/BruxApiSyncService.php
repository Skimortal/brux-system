<?php

namespace App\Service;

use App\Entity\EventCategory;
use App\Entity\EventPrice;
use App\Entity\Production;
use App\Entity\ProductionEvent;
use App\Entity\ProductionPrice;
use App\Entity\Room;
use App\Enum\EventReservationStatus;
use App\Enum\EventStatus;
use App\Repository\EventCategoryRepository;
use App\Repository\ProductionEventRepository;
use App\Repository\ProductionRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imports productions and performances from the WordPress site.
 * Only fields that WordPress owns (see FieldOwnership) are written.
 */
class BruxApiSyncService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private ProductionRepository $productionRepository,
        private ProductionEventRepository $productionEventRepository,
        private RoomRepository $roomRepository,
        private EventCategoryRepository $eventCategoryRepository,
        private FieldOwnership $ownership,
        private AppointmentManager $appointmentManager,
        private LoggerInterface $logger,
        private string $apiUrl,
    ) {}

    /**
     * @return array{productions_created: int, productions_updated: int, events_created: int, events_updated: int, categories_created: int, errors: string[]}
     */
    public function syncFromApi(): array
    {
        $stats = [
            'productions_created' => 0,
            'productions_updated' => 0,
            'events_created' => 0,
            'events_updated' => 0,
            'categories_created' => 0,
            'errors' => [],
        ];
        $syncedAt = new \DateTimeImmutable();

        try {
            $response = $this->httpClient->request('GET', $this->apiUrl, ['timeout' => 30]);
            $data = $response->toArray();

            if (!isset($data['productions']) || !\is_array($data['productions'])) {
                throw new \RuntimeException('Invalid API response format');
            }

            foreach ($data['productions'] as $productionData) {
                try {
                    $this->syncProduction($productionData, $stats, $syncedAt);
                } catch (\Throwable $e) {
                    $stats['errors'][] = sprintf('Produktion %s: %s', $productionData['id'] ?? '?', $e->getMessage());
                    $this->logger->error('Production sync error', [
                        'production_id' => $productionData['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $stats['errors'][] = 'API-Fehler: ' . $e->getMessage();
            $this->logger->error('API sync error', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getLastSyncTime(): ?\DateTimeImmutable
    {
        return $this->productionEventRepository->findLastSyncedAt();
    }

    private function syncProduction(array $data, array &$stats, \DateTimeImmutable $syncedAt): void
    {
        $externalId = isset($data['id']) ? (int) $data['id'] : null;
        if (!$externalId) {
            throw new \RuntimeException('Production has no ID');
        }

        $production = $this->productionRepository->findOneBy(['externalId' => $externalId]);
        if ($production === null) {
            $production = new Production();
            $production->setExternalId($externalId);
            ++$stats['productions_created'];
        } else {
            ++$stats['productions_updated'];
        }

        if ($this->owns('production', 'title') || $production->getTitle() === null) {
            $production->setTitle((string) $this->decode($data['title'] ?? ''));
        }
        if ($this->owns('production', 'permalink')) {
            $production->setPermalink($data['permalink'] ?? null);
        }
        if ($this->owns('production', 'postThumbnailUrl')) {
            $production->setPostThumbnailUrl($data['post_thumbnail_url'] ?? null);
        }
        if ($this->owns('production', 'contentHtml')) {
            $production->setContentHtml($data['content_html'] ?? null);
        }
        if ($this->owns('production', 'excerptHtml')) {
            $production->setExcerptHtml($data['excerpt_html'] ?? null);
        }
        $production->setSyncedAt($syncedAt);
        $this->entityManager->persist($production);

        if ($this->owns('production', 'priceList') && isset($data['prices']) && \is_array($data['prices'])) {
            $this->syncProductionPrices($production, $data['prices']);
        }
        if (isset($data['events']) && \is_array($data['events'])) {
            $this->syncEvents($production, $data['events'], $stats, $syncedAt);
        }
    }

    private function syncProductionPrices(Production $production, array $pricesData): void
    {
        foreach ($production->getPriceList()->toArray() as $oldPrice) {
            $production->getPriceList()->removeElement($oldPrice);
            $this->entityManager->remove($oldPrice);
        }
        foreach ($pricesData as $priceData) {
            $price = new ProductionPrice();
            $price->setPriceIndex($priceData['index'] ?? null);
            $price->setPriceLabel($this->decode($priceData['price_label'] ?? null));
            $price->setCategoryLabel($this->decode($priceData['category_label'] ?? null));
            $price->setParentReserved($priceData['parent_reserved'] ?? null);
            $production->addPriceList($price);
            $this->entityManager->persist($price);
        }
    }

    private function syncEvents(Production $production, array $eventsData, array &$stats, \DateTimeImmutable $syncedAt): void
    {
        foreach ($eventsData as $eventData) {
            try {
                // placeholder rows in WordPress have no date
                if (empty($eventData['date'])) {
                    continue;
                }
                $date = new \DateTime($eventData['date']);
                $eventIndex = isset($eventData['event_index']) ? (int) $eventData['event_index'] : null;

                $event = $this->findEvent($production, $eventIndex, $date, $eventData['time_from'] ?? null);
                if ($event === null) {
                    $event = new ProductionEvent();
                    $production->addEvent($event);
                    ++$stats['events_created'];
                } else {
                    ++$stats['events_updated'];
                }

                $event->setEventIndex($eventIndex);
                if ($this->owns('production_event', 'date') || $event->getDate() === null) {
                    $event->setDate($date);
                }
                if ($this->owns('production_event', 'timeFrom')) {
                    $event->setTimeFrom($eventData['time_from'] ?? null);
                }
                if ($this->owns('production_event', 'timeTo')) {
                    $event->setTimeTo($eventData['time_to'] ?? null);
                }
                if ($this->owns('production_event', 'room') && !empty($eventData['room'])) {
                    $event->setRoom($this->findOrCreateRoom((string) $eventData['room']));
                }
                if ($this->owns('production_event', 'status') && !empty($eventData['status'])) {
                    $event->setStatus(EventStatus::tryFrom($eventData['status']));
                }
                if ($this->owns('production_event', 'reservationStatus') && !empty($eventData['reservation_status'])) {
                    $event->setReservationStatus(EventReservationStatus::tryFrom($eventData['reservation_status']));
                }
                if ($this->owns('production_event', 'quota')) {
                    $event->setQuota($eventData['quota'] ?? null);
                }
                if ($this->owns('production_event', 'incomingTotal')) {
                    $event->setIncomingTotal($eventData['incoming_total'] ?? null);
                }
                if ($this->owns('production_event', 'freeSeats')) {
                    $event->setFreeSeats($eventData['free_seats'] ?? null);
                }
                if ($this->owns('production_event', 'reservationNote')) {
                    $event->setReservationNote($eventData['reservation_note'] ?? null);
                }
                if ($this->owns('production_event', 'reservations')) {
                    $event->setReservations(\is_array($eventData['reservations'] ?? null) ? $eventData['reservations'] : []);
                }
                $event->setSyncedAt($syncedAt);
                $this->entityManager->persist($event);

                if ($this->owns('production_event', 'categories') && isset($eventData['categories']) && \is_array($eventData['categories'])) {
                    $this->syncEventCategories($event, $eventData['categories'], $stats);
                }
                if ($this->owns('production_event', 'priceList') && isset($eventData['prices']) && \is_array($eventData['prices'])) {
                    $this->syncEventPrices($event, $eventData['prices']);
                }

                // keep the staffing appointment on the (possibly moved) performance
                if ($event->getAppointment() !== null) {
                    $this->appointmentManager->alignWithPerformance($event->getAppointment(), $event);
                }
            } catch (\Throwable $e) {
                $stats['errors'][] = sprintf('Vorstellung %s (%s): %s', $eventData['event_index'] ?? '?', $production->getTitle(), $e->getMessage());
                $this->logger->error('Event sync error', [
                    'event_index' => $eventData['event_index'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Matches by event index; rows without an index are matched by date and start time.
     */
    private function findEvent(Production $production, ?int $eventIndex, \DateTimeInterface $date, ?string $timeFrom): ?ProductionEvent
    {
        if ($eventIndex !== null) {
            foreach ($production->getEvents() as $existing) {
                if ($existing->getEventIndex() === $eventIndex) {
                    return $existing;
                }
            }

            return null;
        }
        foreach ($production->getEvents() as $existing) {
            if ($existing->getDate()?->format('Y-m-d') === $date->format('Y-m-d') && $existing->getTimeFrom() === $timeFrom) {
                return $existing;
            }
        }

        return null;
    }

    private function findOrCreateRoom(string $externalId): Room
    {
        $room = $this->roomRepository->findOneBy(['externalId' => $externalId]);
        if ($room === null) {
            $room = new Room();
            $room->setExternalId($externalId);
            $room->setName(ucfirst($externalId));
            $this->entityManager->persist($room);
            // make the new room visible to later lookups within the same run
            $this->entityManager->flush();
        }

        return $room;
    }

    private function syncEventCategories(ProductionEvent $event, array $categoriesData, array &$stats): void
    {
        $event->getCategories()->clear();

        foreach ($categoriesData as $categoryData) {
            $externalId = $categoryData['id'] ?? null;
            if (!$externalId) {
                continue;
            }
            $category = $this->eventCategoryRepository->findByExternalId($externalId);
            if ($category === null) {
                $category = new EventCategory();
                $category->setExternalId($externalId);
                ++$stats['categories_created'];
            }
            $category->setName((string) $this->decode($categoryData['name'] ?? ''));
            $category->setSlug($categoryData['slug'] ?? null);

            $this->entityManager->persist($category);
            $event->addCategory($category);
        }
    }

    private function syncEventPrices(ProductionEvent $event, array $pricesData): void
    {
        foreach ($event->getPriceList()->toArray() as $oldPrice) {
            $event->getPriceList()->removeElement($oldPrice);
            $this->entityManager->remove($oldPrice);
        }
        foreach ($pricesData as $priceData) {
            $price = new EventPrice();
            $price->setPriceIndex($priceData['price_index'] ?? null);
            $price->setPriceLabel($this->decode($priceData['price_label'] ?? null));
            $price->setCategoryLabel($this->decode($priceData['category_label'] ?? null));
            $price->setReservedSeats($priceData['reserved_seats'] ?? null);
            $price->setIncomingReservations($priceData['incoming_reservations'] ?? null);
            $event->addPriceList($price);
            $this->entityManager->persist($price);
        }
    }

    private function owns(string $entity, string $field): bool
    {
        return $this->ownership->isWordPressField($entity, $field);
    }

    private function decode(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        return html_entity_decode($text, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
    }
}
