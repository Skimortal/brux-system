<?php

namespace App\Service;

use App\Entity\EventCategory;
use App\Entity\EventPrice;
use App\Entity\Production;
use App\Entity\ProductionCategory;
use App\Entity\ProductionEvent;
use App\Entity\ProductionPrice;
use App\Entity\Room;
use App\Enum\EventReservationStatus;
use App\Enum\EventStatus;
use App\Repository\EventCategoryRepository;
use App\Repository\ProductionCategoryRepository;
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
    /** Event categories that WordPress sets itself. */
    public const SERVER_CATEGORIES = ['ausverkauft-event'];

    /** Drafts in WordPress that are templates for new posts, not productions. */
    private const TEMPLATE_TITLE = '/\bVorlage\b/u';

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private ProductionRepository $productionRepository,
        private ProductionEventRepository $productionEventRepository,
        private RoomRepository $roomRepository,
        private EventCategoryRepository $eventCategoryRepository,
        private ProductionCategoryRepository $productionCategoryRepository,
        private WebsiteContent $websiteContent,
        private FieldOwnership $ownership,
        private AppointmentManager $appointmentManager,
        private WordPressPushService $push,
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
        $this->push->retryProductions($this->productionRepository->findFailedPushes());

        try {
            foreach ($this->fetchProductions($stats) as $productionData) {
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
            $this->push->retryProductionEvents($this->productionEventRepository->findFailedPushes());
        } catch (\Throwable $e) {
            $stats['errors'][] = 'API-Fehler: ' . $e->getMessage();
            $this->logger->error('API sync error', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    /** Where the import reads from: the protected API when it is set up, else the old public one. */
    public function getApiUrl(): string
    {
        return $this->push->isConfigured() ? rtrim($this->push->getApiUrl(), '/') . '/productions' : $this->apiUrl;
    }

    /**
     * The productions in the shape the import works with (that of the old public API):
     * "id" is the WordPress post id and categories are objects with id, name and slug.
     * The protected API also lists drafts; private posts and template drafts are left out.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchProductions(array &$stats): array
    {
        if (!$this->push->isConfigured()) {
            $data = $this->httpClient->request('GET', $this->apiUrl, ['timeout' => 30])->toArray();
            if (!isset($data['productions']) || !\is_array($data['productions'])) {
                throw new \RuntimeException('Invalid API response format');
            }

            return $data['productions'];
        }

        $categories = $this->push->fetchCategories();
        if ($categories['status'] !== 'success') {
            throw new \RuntimeException('Kategorien: ' . ($categories['message'] ?? 'unbekannter Fehler'));
        }
        $this->syncCategoryList($categories['categories'], $stats);
        $bySlug = [];
        foreach ($categories['categories'] as $category) {
            $bySlug[$category['slug'] ?? ''] = ['id' => $category['term_id'] ?? null, 'name' => $category['name'] ?? '', 'slug' => $category['slug'] ?? null];
        }
        $toObjects = static fn (array $slugs): array => array_values(array_filter(array_map(static fn ($slug) => $bySlug[$slug] ?? null, $slugs)));

        $result = $this->push->fetchProductions();
        if ($result['status'] !== 'success') {
            throw new \RuntimeException($result['message'] ?? 'unbekannter Fehler');
        }
        $productions = [];
        foreach ($result['productions'] as $production) {
            $status = $production['post_status'] ?? 'publish';
            if ($status === 'private' || ($status === 'draft' && preg_match(self::TEMPLATE_TITLE, (string) ($production['title'] ?? '')))) {
                continue;
            }
            $production['id'] = $production['external_id'] ?? null;
            $production['categories'] = $toObjects($production['categories'] ?? []);
            foreach ($production['events'] ?? [] as $index => $event) {
                $production['events'][$index]['categories'] = $toObjects($event['categories'] ?? []);
                $production['events'][$index]['time_to'] = ($event['time_to'] ?? null) ?: null;
            }
            $productions[] = $production;
        }

        return $productions;
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

        $title = (string) $this->decode($data['title'] ?? '');
        $production = $this->productionRepository->findOneBy(['externalId' => $externalId])
            ?? $this->productionRepository->findUnconfirmedPush($title);
        $isNew = $production === null;
        if ($isNew) {
            $production = (new Production())->setWebsiteStatus(($data['post_status'] ?? 'publish') === 'draft' ? 'draft' : 'publish');
            ++$stats['productions_created'];
        } else {
            ++$stats['productions_updated'];
        }
        $production->setExternalId($externalId);
        $takes = fn (string $field): bool => $isNew || $this->owns('production', $field);

        if ($takes('title')) {
            $production->setTitle($title);
        }
        if ($takes('permalink')) {
            $production->setPermalink($data['permalink'] ?? null);
        }
        if ($takes('postThumbnailUrl')) {
            $production->setPostThumbnailUrl($data['post_thumbnail_url'] ?? null);
        }
        if ($takes('contentHtml')) {
            $production->setContentHtml($data['content_html'] ?? null);
        }
        if ($takes('excerptHtml')) {
            $production->setExcerptHtml($data['excerpt_html'] ?? null);
        }
        if ($isNew) {
            $this->websiteContent->fillSections($production);
        }
        if ($takes('categories') && isset($data['categories']) && \is_array($data['categories'])) {
            $this->syncProductionCategories($production, $data['categories']);
        }
        $production->setSyncedAt($syncedAt);
        $this->entityManager->persist($production);

        if ($takes('priceList') && isset($data['prices']) && \is_array($data['prices'])) {
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
                $isNew = $event === null;
                if ($isNew) {
                    $event = new ProductionEvent();
                    $production->addEvent($event);
                    ++$stats['events_created'];
                } else {
                    ++$stats['events_updated'];
                }
                $takes = fn (string $field): bool => $isNew || $this->owns('production_event', $field);

                $event->setEventIndex($eventIndex);
                if ($takes('date')) {
                    $event->setDate($date);
                }
                if ($takes('timeFrom')) {
                    $event->setTimeFrom($eventData['time_from'] ?? null);
                }
                if ($takes('timeTo')) {
                    $event->setTimeTo($eventData['time_to'] ?? null);
                }
                if ($takes('room') && !empty($eventData['room'])) {
                    $event->setRoom($this->findOrCreateRoom((string) $eventData['room']));
                }
                if ($takes('status') && !empty($eventData['status'])) {
                    $event->setStatus(EventStatus::tryFrom($eventData['status']));
                }
                if ($takes('reservationStatus') && !empty($eventData['reservation_status'])) {
                    $event->setReservationStatus(EventReservationStatus::tryFrom($eventData['reservation_status']));
                }
                if ($takes('quota')) {
                    $event->setQuota($eventData['quota'] ?? null);
                }
                if ($takes('incomingTotal')) {
                    $event->setIncomingTotal($eventData['incoming_total'] ?? null);
                }
                if ($takes('freeSeats')) {
                    $event->setFreeSeats($eventData['free_seats'] ?? null);
                }
                if ($takes('reservationNote')) {
                    $event->setReservationNote($eventData['reservation_note'] ?? null);
                }
                if ($takes('reservations')) {
                    $event->setReservations(\is_array($eventData['reservations'] ?? null) ? $eventData['reservations'] : []);
                }
                $event->setSyncedAt($syncedAt);
                $this->entityManager->persist($event);

                if (isset($eventData['categories']) && \is_array($eventData['categories'])) {
                    $this->syncEventCategories($event, $eventData['categories'], $stats, $takes('categories'));
                }
                if (isset($eventData['prices']) && \is_array($eventData['prices'])) {
                    $takes('priceList')
                        ? $this->syncEventPrices($event, $eventData['prices'])
                        : $this->syncIncomingReservations($event, $eventData['prices']);
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
     * Matches by event index; otherwise by date and start time, among performances
     * WordPress has not numbered yet (created here, push still pending).
     */
    private function findEvent(Production $production, ?int $eventIndex, \DateTimeInterface $date, ?string $timeFrom): ?ProductionEvent
    {
        if ($eventIndex !== null) {
            foreach ($production->getEvents() as $existing) {
                if ($existing->getEventIndex() === $eventIndex) {
                    return $existing;
                }
            }
        }
        foreach ($production->getEvents() as $existing) {
            if (($eventIndex === null || $existing->getEventIndex() === null)
                && $existing->getDate()?->format('Y-m-d') === $date->format('Y-m-d')
                && $existing->getTimeFrom() === $timeFrom
            ) {
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

    /**
     * Takes all categories, or only the ones WordPress sets itself (sold out) when the
     * categories are maintained here.
     */
    private function syncEventCategories(ProductionEvent $event, array $categoriesData, array &$stats, bool $all): void
    {
        foreach ($event->getCategories()->toArray() as $category) {
            if ($all || \in_array($category->getSlug(), self::SERVER_CATEGORIES, true)) {
                $event->removeCategory($category);
            }
        }

        foreach ($categoriesData as $categoryData) {
            $externalId = $categoryData['id'] ?? null;
            if (!$externalId || (!$all && !\in_array($categoryData['slug'] ?? null, self::SERVER_CATEGORIES, true))) {
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

    /**
     * Takes over production categories created in WordPress, and their colour, which
     * WordPress keeps as a hex code in the description, unless one is set here.
     */
    private function syncCategoryList(array $categories, array &$stats): void
    {
        foreach ($categories as $data) {
            $externalId = (int) ($data['term_id'] ?? 0);
            // "-event" categories belong to performances and are synced with them
            if ($externalId === 0 || str_ends_with((string) ($data['slug'] ?? ''), '-event')) {
                continue;
            }
            $category = $this->productionCategoryRepository->findByExternalId($externalId);
            if ($category === null) {
                $category = (new ProductionCategory())->setExternalId($externalId);
                $this->entityManager->persist($category);
                ++$stats['categories_created'];
            }
            $category->setName((string) $this->decode($data['name'] ?? ''))->setSlug($data['slug'] ?? null);
            $color = trim((string) ($data['description'] ?? ''));
            if ($category->getColor() === null && preg_match('/^#[0-9a-f]{6}$/i', $color)) {
                $category->setColor(strtolower($color));
            }
        }
        $this->entityManager->flush();
    }

    private function syncProductionCategories(Production $production, array $categoriesData): void
    {
        $production->getCategories()->clear();
        foreach ($categoriesData as $categoryData) {
            $externalId = isset($categoryData['id']) ? (int) $categoryData['id'] : null;
            if (!$externalId) {
                continue;
            }
            $category = $this->productionCategoryRepository->findByExternalId($externalId);
            $isNew = $category === null;
            $category ??= (new ProductionCategory())->setExternalId($externalId);
            $category->setName((string) $this->decode($categoryData['name'] ?? ''))->setSlug($categoryData['slug'] ?? null);
            if ($isNew) {
                $this->entityManager->persist($category);
                // make it visible to the next production of this run
                $this->entityManager->flush();
            }
            $production->addCategory($category);
        }
    }

    /** Updates the reservation counts WordPress keeps per price, leaving the prices as they are here. */
    private function syncIncomingReservations(ProductionEvent $event, array $pricesData): void
    {
        $incoming = [];
        foreach ($pricesData as $priceData) {
            if (isset($priceData['price_index'])) {
                $incoming[(int) $priceData['price_index']] = $priceData['incoming_reservations'] ?? null;
            }
        }
        foreach ($event->getPriceList() as $price) {
            if ($price->getPriceIndex() !== null && \array_key_exists($price->getPriceIndex(), $incoming)) {
                $price->setIncomingReservations($incoming[$price->getPriceIndex()]);
            }
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
