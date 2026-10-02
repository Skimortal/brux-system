<?php

namespace App\Service;

use App\Entity\Production;
use App\Entity\ProductionEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Writes productions and performances to the WordPress site via the brux-crud/v1 API.
 *
 * PUT merges on the WordPress side: fields left out stay untouched. Records that already
 * exist in WordPress therefore only carry the fields this system owns, so a push never
 * overwrites newer WordPress edits with the copy from the last import.
 */
class WordPressPushService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private FieldOwnership $ownership,
        private LoggerInterface $logger,
        private string $wordpressApiUrl,
        private string $wordpressApiUser = '',
        private string $wordpressApiPassword = '',
    ) {}

    public function isConfigured(): bool
    {
        return trim($this->wordpressApiUrl) !== '' && trim($this->wordpressApiUser) !== '' && trim($this->wordpressApiPassword) !== '';
    }

    public function getApiUrl(): string
    {
        return $this->wordpressApiUrl;
    }

    /**
     * @return array{status: string, status_code?: int, message?: string, response?: mixed}
     */
    public function checkConnection(): array
    {
        return $this->request('GET', '/system-check');
    }

    /**
     * Pushes a production; a production that is new to WordPress takes its performances along.
     */
    public function pushProduction(Production $production): array
    {
        $created = $production->getExternalId() === null;
        $result = $this->request('PUT', '/production', $this->buildProductionPayload($production));
        if ($result['status'] === 'skipped') {
            return $result;
        }

        $production->markPushed($result['status'] === 'success' ? null : ($result['message'] ?? 'Unbekannter Fehler'));
        if ($result['status'] === 'success' && $created) {
            $externalId = $result['response']['external_id'] ?? null;
            if ($externalId !== null) {
                $production->setExternalId((int) $externalId);
            } else {
                $this->logger->warning('WordPress Push: Antwort ohne external_id', ['production' => $production->getId()]);
            }
        }
        $this->entityManager->flush();

        if ($result['status'] === 'success' && $created && $production->getExternalId() !== null) {
            foreach ($production->getEvents() as $event) {
                // indexes given here before the production existed in WordPress mean nothing there
                $event->setEventIndex(null);
                $eventResult = $this->pushProductionEvent($event);
                if ($eventResult['status'] === 'error') {
                    $result = ['status' => 'error', 'message' => 'Vorstellung vom ' . $event->getDate()?->format('d.m.Y') . ': ' . ($eventResult['message'] ?? '')];
                }
            }
        }

        return $result;
    }

    public function pushProductionEvent(ProductionEvent $event): array
    {
        $production = $event->getProduction();
        // WordPress needs the production before its performances; pushing it takes them along
        if ($production !== null && $production->getExternalId() === null) {
            return $this->pushProduction($production);
        }

        $created = $event->getEventIndex() === null;
        $result = $this->request('PUT', '/event', $this->buildEventPayload($event));
        if ($result['status'] === 'skipped') {
            return $result;
        }

        $event->markPushed($result['status'] === 'success' ? null : ($result['message'] ?? 'Unbekannter Fehler'));
        if ($result['status'] === 'success' && $created) {
            $response = $result['response'] ?? [];
            $eventIndex = $response['event_index'] ?? $response['event']['event_index'] ?? null;
            if ($eventIndex !== null) {
                $event->setEventIndex((int) $eventIndex);
            } else {
                $this->logger->warning('WordPress Push: Antwort ohne event_index', ['event' => $event->getId()]);
            }
        }
        $this->entityManager->flush();

        return $result;
    }

    /**
     * Removes a production from WordPress (into the trash) before it is deleted here;
     * WordPress refuses while it holds reservations for it.
     */
    public function deleteProduction(Production $production): array
    {
        if ($production->getExternalId() === null) {
            return ['status' => 'skipped', 'message' => 'Nicht in WordPress'];
        }

        return $this->request('DELETE', '/productions/' . $production->getExternalId());
    }

    /**
     * Removes a performance from WordPress before it is deleted here. WordPress renumbers
     * the later performances of the production, so the local indexes follow.
     */
    public function deleteProductionEvent(ProductionEvent $event): array
    {
        $production = $event->getProduction();
        $eventIndex = $event->getEventIndex();
        if ($production?->getExternalId() === null || $eventIndex === null) {
            return ['status' => 'skipped', 'message' => 'Nicht in WordPress'];
        }

        $result = $this->request('DELETE', '/productions/' . $production->getExternalId() . '/events/' . $eventIndex);
        if ($result['status'] === 'success') {
            $event->setEventIndex(null);
            foreach ($production->getEvents() as $other) {
                if ($other->getEventIndex() !== null && $other->getEventIndex() > $eventIndex) {
                    $other->setEventIndex($other->getEventIndex() - 1);
                }
            }
            $this->entityManager->flush();
        }

        return $result;
    }

    /**
     * Pushes productions again whose last push failed. Runs before an import, so a
     * production WordPress created without us learning its id gets linked (WordPress
     * recognises it by our id) instead of imported a second time.
     *
     * @param Production[] $productions
     */
    public function retryProductions(array $productions): void
    {
        foreach ($productions as $production) {
            $this->pushProduction($production);
        }
    }

    /**
     * Pushes performances again whose last push failed. Runs after an import, which has
     * already linked performances WordPress created without us learning their index.
     *
     * @param ProductionEvent[] $events
     */
    public function retryProductionEvents(array $events): void
    {
        foreach ($events as $event) {
            if ($event->getLastPushError() !== null) {
                $this->pushProductionEvent($event);
            }
        }
    }

    private function buildProductionPayload(Production $production): array
    {
        $payload = [
            'id' => $production->getId(),
            'external_id' => $production->getExternalId(),
            'needs_lighting_technician' => $production->isNeedsLightingTechnician(),
            'needs_sound_technician' => $production->isNeedsSoundTechnician(),
            'needs_setup_technician' => $production->isNeedsSetupTechnician(),
            'grandstand' => $production->getGrandstand(),
            'post_status' => $production->getWebsiteStatus(),
            'technicians' => [],
            'contact_persons' => [],
        ];

        foreach ($production->getTechnicians() as $technician) {
            $payload['technicians'][] = [
                'name' => $technician->getName(),
                'email' => $technician->getEmail(),
                'phone' => $technician->getPhone(),
            ];
        }

        foreach ($production->getContactPersons() as $contactPerson) {
            $payload['contact_persons'][] = [
                'name' => $contactPerson->getName(),
                'email' => $contactPerson->getEmail(),
                'phone' => $contactPerson->getPhone(),
                'hauptansprechperson' => $contactPerson->isHauptansprechperson(),
            ];
        }

        $isNew = $production->getExternalId() === null;
        $owned = fn (string $field): bool => $isNew || !$this->ownership->isWordPressField('production', $field);

        if ($owned('title')) {
            $payload['title'] = $production->getTitle();
        }
        if ($owned('postThumbnailUrl')) {
            $payload['post_thumbnail_url'] = $production->getPostThumbnailUrl();
        }
        if ($owned('contentHtml')) {
            $payload['content_html'] = $production->getContentHtml();
        }
        if ($owned('excerptHtml')) {
            $payload['excerpt_html'] = $production->getExcerptHtml();
        }
        if ($owned('priceList')) {
            $payload['prices'] = [];
            foreach ($production->getPriceList() as $price) {
                $payload['prices'][] = [
                    'index' => $price->getPriceIndex(),
                    'price_label' => $price->getPriceLabel(),
                    'category_label' => $price->getCategoryLabel(),
                    'parent_reserved' => $price->getParentReserved(),
                ];
            }
        }

        return $payload;
    }

    private function buildEventPayload(ProductionEvent $event): array
    {
        $payload = [
            'id' => $event->getId(),
            'production_external_id' => $event->getProduction()?->getExternalId(),
            'event_index' => $event->getEventIndex(),
            'contact_persons' => [],
        ];

        foreach ($event->getContactPersons() as $contactPerson) {
            $payload['contact_persons'][] = [
                'name' => $contactPerson->getName(),
                'email' => $contactPerson->getEmail(),
                'phone' => $contactPerson->getPhone(),
                'hauptansprechperson' => $contactPerson->isHauptansprechperson(),
            ];
        }

        $isNew = $event->getEventIndex() === null;
        $owned = fn (string $field): bool => $isNew || !$this->ownership->isWordPressField('production_event', $field);

        if ($owned('date')) {
            $payload['date'] = $event->getDate()?->format('Y-m-d');
        }
        if ($owned('timeFrom')) {
            $payload['time_from'] = $event->getTimeFrom();
        }
        if ($owned('timeTo')) {
            $payload['time_to'] = $event->getTimeTo();
        }
        if ($owned('room') && $event->getRoom()?->getExternalId() !== null) {
            $payload['room'] = $event->getRoom()->getExternalId();
        }
        if ($owned('status') && $event->getStatus() !== null) {
            $payload['status'] = $event->getStatus()->value;
        }
        if ($owned('reservationStatus') && $event->getReservationStatus() !== null) {
            $payload['reservation_status'] = $event->getReservationStatus()->value;
        }
        if ($owned('quota') && $event->getQuota() !== null) {
            $payload['quota'] = $event->getQuota();
        }
        if ($owned('reservationNote') && $event->getReservationNote() !== null) {
            $payload['reservation_note'] = $event->getReservationNote();
        }
        if ($owned('categories')) {
            $payload['categories'] = [];
            foreach ($event->getCategories() as $category) {
                $slug = $category->getSlug();
                if ($slug !== null && !\in_array($slug, BruxApiSyncService::SERVER_CATEGORIES, true)) {
                    $payload['categories'][] = $slug;
                }
            }
        }
        if ($owned('priceList')) {
            $payload['prices'] = [];
            foreach ($event->getPriceList() as $price) {
                $payload['prices'][] = [
                    'price_index' => $price->getPriceIndex(),
                    'price_label' => $price->getPriceLabel(),
                    'category_label' => $price->getCategoryLabel(),
                    'reserved_seats' => $price->getReservedSeats(),
                ];
            }
        }

        return $payload;
    }

    /**
     * @return array{status: string, status_code?: int, message?: string, response?: mixed}
     */
    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        if (!$this->isConfigured()) {
            $this->logger->info('WordPress Push: nicht konfiguriert, Request übersprungen.', ['endpoint' => $endpoint]);

            return ['status' => 'skipped', 'message' => 'WordPress API nicht konfiguriert'];
        }

        $url = rtrim($this->wordpressApiUrl, '/') . $endpoint;
        $options = [
            'auth_basic' => [trim($this->wordpressApiUser), trim($this->wordpressApiPassword)],
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 15,
        ];
        if ($data !== null) {
            $options['json'] = $data;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);

            if ($statusCode < 200 || $statusCode >= 300 || ($responseData['success'] ?? true) === false) {
                $message = isset($responseData['message']) ? (string) $responseData['message'] : 'HTTP ' . $statusCode;
                $this->logger->error('WordPress Push abgelehnt', ['method' => $method, 'url' => $url, 'status_code' => $statusCode, 'message' => $message]);

                return ['status' => 'error', 'status_code' => $statusCode, 'message' => $message];
            }

            $this->logger->info('WordPress Push erfolgreich', ['method' => $method, 'url' => $url, 'status_code' => $statusCode]);

            return ['status' => 'success', 'status_code' => $statusCode, 'response' => $responseData];
        } catch (\Throwable $e) {
            $this->logger->error('WordPress Push fehlgeschlagen', ['method' => $method, 'url' => $url, 'error' => $e->getMessage()]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
