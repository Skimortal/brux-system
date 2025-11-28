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
use App\Repository\ProductionRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BruxApiSyncService
{
    private const API_URL = 'https://www.brux.at/wp-json/brux/v1/events';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private ProductionRepository $productionRepository,
        private RoomRepository $roomRepository,
        private EventCategoryRepository $eventCategoryRepository,
        private LoggerInterface $logger
    ) {}

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

        try {
            $response = $this->httpClient->request('GET', self::API_URL);
            $data = $response->toArray();

            if (!isset($data['productions']) || !is_array($data['productions'])) {
                throw new \Exception('Invalid API response format');
            }

            foreach ($data['productions'] as $productionData) {
                try {
                    $this->syncProduction($productionData, $stats);
                } catch (\Exception $e) {
                    $stats['errors'][] = sprintf(
                        'Production ID %s: %s',
                        $productionData['id'] ?? 'unknown',
                        $e->getMessage()
                    );
                    $this->logger->error('Production sync error', [
                        'production_id' => $productionData['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->entityManager->flush();

        } catch (\Exception $e) {
            $stats['errors'][] = 'API Error: ' . $e->getMessage();
            $this->logger->error('API sync error', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    private function syncProduction(array $data, array &$stats): void
    {
        $externalId = $data['id'] ?? null;
        if (!$externalId) {
            throw new \Exception('Production has no ID');
        }

        // Find or create production
        $production = $this->productionRepository->findOneBy(['externalId' => $externalId]);
        $isNew = $production === null;

        if ($isNew) {
            $production = new Production();
            $stats['productions_created']++;
        } else {
            $stats['productions_updated']++;
        }

        // Update production fields
        $production->setExternalId($externalId);
        $production->setTitle($data['title'] ?? '');
        $production->setPermalink($data['permalink'] ?? null);
        $production->setPostThumbnailUrl($data['post_thumbnail_url'] ?? null);
        $production->setContentHtml($data['content_html'] ?? null);
        $production->setExcerptHtml($data['excerpt_html'] ?? null);

        $this->entityManager->persist($production);

        // Sync production prices
        if (isset($data['prices']) && is_array($data['prices'])) {
            $this->syncProductionPrices($production, $data['prices']);
        }

        // Sync events
        if (isset($data['events']) && is_array($data['events'])) {
            $this->syncEvents($production, $data['events'], $stats);
        }
    }

    private function syncProductionPrices(Production $production, array $pricesData): void
    {
        // Remove old prices
        foreach ($production->getPriceList() as $oldPrice) {
            $this->entityManager->remove($oldPrice);
        }

        // Add new prices
        foreach ($pricesData as $priceData) {
            $price = new ProductionPrice();
            $price->setProduction($production);
            $price->setPriceIndex($priceData['index'] ?? null);
            $price->setPriceLabel($priceData['price_label'] ?? null);
            $price->setCategoryLabel($priceData['category_label'] ?? null);
            $price->setParentReserved($priceData['parent_reserved'] ?? null);

            $this->entityManager->persist($price);
        }
    }

    private function syncEvents(Production $production, array $eventsData, array &$stats): void
    {
        foreach ($eventsData as $eventData) {
            try {
                $eventIndex = $eventData['event_index'] ?? null;

                // Skip events without date (placeholder events)
                if (empty($eventData['date'])) {
                    continue;
                }

                // Find existing event by production and event_index
                $event = null;
                foreach ($production->getEvents() as $existingEvent) {
                    if ($existingEvent->getEventIndex() === $eventIndex) {
                        $event = $existingEvent;
                        break;
                    }
                }

                $isNew = $event === null;

                if ($isNew) {
                    $event = new ProductionEvent();
                    $event->setProduction($production);
                    $stats['events_created']++;
                } else {
                    $stats['events_updated']++;
                }

                // Update event fields
                $event->setEventIndex($eventIndex);

                // Parse date
                if (!empty($eventData['date'])) {
                    try {
                        $date = new \DateTime($eventData['date']);
                        $event->setDate($date);
                    } catch (\Exception $e) {
                        $this->logger->warning('Invalid date format', ['date' => $eventData['date']]);
                    }
                }

                $event->setTimeFrom($eventData['time_from'] ?? null);
                $event->setTimeTo($eventData['time_to'] ?? null);

                // Set room by external_id
                if (!empty($eventData['room'])) {
                    $room = $this->roomRepository->findOneBy(['externalId' => $eventData['room']]);
                    if (!$room) {
                        // Create room if not exists
                        $room = new Room();
                        $room->setExternalId($eventData['room']);
                        $room->setName(ucfirst($eventData['room']));
                        $this->entityManager->persist($room);
                    }
                    $event->setRoom($room);
                }

                // Set status enums
                if (!empty($eventData['status'])) {
                    $event->setStatus(EventStatus::tryFrom($eventData['status']));
                }
                if (!empty($eventData['reservation_status'])) {
                    $event->setReservationStatus(EventReservationStatus::tryFrom($eventData['reservation_status']));
                }

                $event->setQuota($eventData['quota'] ?? null);
                $event->setIncomingTotal($eventData['incoming_total'] ?? null);
                $event->setFreeSeats($eventData['free_seats'] ?? null);
                $event->setReservationNote($eventData['reservation_note'] ?? null);
                $event->setReservations($eventData['reservations'] ?? []);

                $this->entityManager->persist($event);

                // Sync categories
                if (isset($eventData['categories']) && is_array($eventData['categories'])) {
                    $this->syncEventCategories($event, $eventData['categories'], $stats);
                }

                // Sync event prices
                if (isset($eventData['prices']) && is_array($eventData['prices'])) {
                    $this->syncEventPrices($event, $eventData['prices']);
                }

            } catch (\Exception $e) {
                $stats['errors'][] = sprintf(
                    'Event Index %s: %s',
                    $eventData['event_index'] ?? 'unknown',
                    $e->getMessage()
                );
                $this->logger->error('Event sync error', [
                    'event_index' => $eventData['event_index'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function syncEventCategories(ProductionEvent $event, array $categoriesData, array &$stats): void
    {
        // Clear existing categories
        $event->getCategories()->clear();

        foreach ($categoriesData as $categoryData) {
            $externalId = $categoryData['id'] ?? null;
            if (!$externalId) {
                continue;
            }

            // Find or create category
            $category = $this->eventCategoryRepository->findByExternalId($externalId);

            if (!$category) {
                $category = new EventCategory();
                $category->setExternalId($externalId);
                $stats['categories_created']++;
            }

            $category->setName($categoryData['name'] ?? '');
            $category->setSlug($categoryData['slug'] ?? null);

            $this->entityManager->persist($category);
            $event->addCategory($category);
        }
    }

    private function syncEventPrices(ProductionEvent $event, array $pricesData): void
    {
        // Remove old prices
        foreach ($event->getPriceList() as $oldPrice) {
            $this->entityManager->remove($oldPrice);
        }

        // Add new prices
        foreach ($pricesData as $priceData) {
            $price = new EventPrice();
            $price->setEvent($event);
            $price->setPriceIndex($priceData['price_index'] ?? null);
            $price->setPriceLabel($priceData['price_label'] ?? null);
            $price->setCategoryLabel($priceData['category_label'] ?? null);
            $price->setReservedSeats($priceData['reserved_seats'] ?? null);
            $price->setIncomingReservations($priceData['incoming_reservations'] ?? null);

            $this->entityManager->persist($price);
        }
    }

    public function getLastSyncTime(): ?\DateTimeInterface
    {
        // TODO: Store last sync time in a settings table or cache
        // For now, return null
        return null;
    }
}
