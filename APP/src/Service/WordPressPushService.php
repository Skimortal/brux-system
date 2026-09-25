<?php

namespace App\Service;

use App\Entity\Production;
use App\Entity\ProductionEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WordPressPushService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $wordpressApiUrl,
        private string $wordpressApiToken = '',
    ) {}

    public function isConfigured(): bool
    {
        return trim($this->wordpressApiUrl) !== '';
    }

    public function pushProduction(Production $production): array
    {
        $data = $this->buildProductionPayload($production);
        $result = $this->sendPutRequest('/production', $data);
        if ($result['status'] !== 'skipped') {
            $production->markPushed($result['status'] === 'success' ? null : ($result['message'] ?? 'Unbekannter Fehler'));
            $this->entityManager->flush();
        }

        // WordPress gibt external_id zurück → bei uns speichern
        if ($result['status'] === 'success'
            && isset($result['response']['external_id'])
            && $production->getExternalId() === null
        ) {
            $production->setExternalId((int) $result['response']['external_id']);
            $this->entityManager->flush();
        }

        return $result;
    }

    public function pushProductionEvent(ProductionEvent $event): array
    {
        $data = $this->buildEventPayload($event);
        $result = $this->sendPutRequest('/event', $data);
        if ($result['status'] !== 'skipped') {
            $event->markPushed($result['status'] === 'success' ? null : ($result['message'] ?? 'Unbekannter Fehler'));
            $this->entityManager->flush();
        }

        return $result;
    }

    private function buildProductionPayload(Production $production): array
    {
        $payload = [
            'id' => $production->getId(),
            'external_id' => $production->getExternalId(),
            'title' => $production->getTitle(),
            'permalink' => $production->getPermalink(),
            'post_thumbnail_url' => $production->getPostThumbnailUrl(),
            'content_html' => $production->getContentHtml(),
            'excerpt_html' => $production->getExcerptHtml(),
            'needs_lighting_technician' => $production->isNeedsLightingTechnician(),
            'needs_sound_technician' => $production->isNeedsSoundTechnician(),
            'needs_setup_technician' => $production->isNeedsSetupTechnician(),
            'grandstand' => $production->getGrandstand(),
            'prices' => [],
            'technicians' => [],
            'contact_persons' => [],
            'events' => [],
        ];

        foreach ($production->getPriceList() as $price) {
            $payload['prices'][] = [
                'index' => $price->getPriceIndex(),
                'price_label' => $price->getPriceLabel(),
                'category_label' => $price->getCategoryLabel(),
                'parent_reserved' => $price->getParentReserved(),
            ];
        }

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

        foreach ($production->getEvents() as $event) {
            $payload['events'][] = $this->buildEventPayload($event);
        }

        return $payload;
    }

    private function buildEventPayload(ProductionEvent $event): array
    {
        $payload = [
            'id' => $event->getId(),
            'event_index' => $event->getEventIndex(),
            'production_id' => $event->getProduction()?->getId(),
            'production_external_id' => $event->getProduction()?->getExternalId(),
            'date' => $event->getDate()?->format('Y-m-d'),
            'time_from' => $event->getTimeFrom(),
            'time_to' => $event->getTimeTo(),
            'room' => $event->getRoom()?->getExternalId(),
            'status' => $event->getStatus()?->value,
            'reservation_status' => $event->getReservationStatus()?->value,
            'quota' => $event->getQuota(),
            'incoming_total' => $event->getIncomingTotal(),
            'free_seats' => $event->getFreeSeats(),
            'reservation_note' => $event->getReservationNote(),
            'reservations' => $event->getReservations(),
            'categories' => [],
            'prices' => [],
            'contact_persons' => [],
        ];

        foreach ($event->getCategories() as $category) {
            $payload['categories'][] = [
                'id' => $category->getExternalId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
            ];
        }

        foreach ($event->getPriceList() as $price) {
            $payload['prices'][] = [
                'price_index' => $price->getPriceIndex(),
                'price_label' => $price->getPriceLabel(),
                'category_label' => $price->getCategoryLabel(),
                'reserved_seats' => $price->getReservedSeats(),
                'incoming_reservations' => $price->getIncomingReservations(),
            ];
        }

        foreach ($event->getContactPersons() as $contactPerson) {
            $payload['contact_persons'][] = [
                'name' => $contactPerson->getName(),
                'email' => $contactPerson->getEmail(),
                'phone' => $contactPerson->getPhone(),
                'hauptansprechperson' => $contactPerson->isHauptansprechperson(),
            ];
        }

        return $payload;
    }

    private function sendPutRequest(string $endpoint, array $data): array
    {
        if (empty($this->wordpressApiUrl)) {
            $this->logger->info('WordPress Push: URL nicht konfiguriert, Request übersprungen.', [
                'endpoint' => $endpoint,
            ]);

            return ['status' => 'skipped', 'message' => 'WordPress API URL nicht konfiguriert'];
        }

        $url = rtrim($this->wordpressApiUrl, '/') . $endpoint;

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
            if (trim($this->wordpressApiToken) !== '') {
                $headers['Authorization'] = 'Bearer ' . trim($this->wordpressApiToken);
            }
            $response = $this->httpClient->request('PUT', $url, [
                'json' => $data,
                'headers' => $headers,
                'timeout' => 15,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray(false);

            if ($statusCode < 200 || $statusCode >= 300) {
                $message = \is_array($responseData) && isset($responseData['message']) ? (string) $responseData['message'] : 'HTTP ' . $statusCode;
                $this->logger->error('WordPress Push abgelehnt', ['url' => $url, 'status_code' => $statusCode, 'message' => $message]);

                return ['status' => 'error', 'status_code' => $statusCode, 'message' => $message];
            }

            $this->logger->info('WordPress Push erfolgreich', [
                'url' => $url,
                'status_code' => $statusCode,
            ]);

            return ['status' => 'success', 'status_code' => $statusCode, 'response' => $responseData];
        } catch (\Throwable $e) {
            $this->logger->error('WordPress Push fehlgeschlagen', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
