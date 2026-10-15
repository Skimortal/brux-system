<?php

namespace App\Tests\Unit;

use App\Entity\EventCategory;
use App\Entity\Production;
use App\Entity\ProductionCategory;
use App\Entity\ProductionEvent;
use App\Service\FieldOwnership;
use App\Service\WordPressPushService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WordPressPushServiceTest extends TestCase
{
    /** @var list<array{method: string, url: string, body: ?array, auth: ?string}> */
    private array $requests = [];

    public function testNewProductionIsCreatedWithItsPerformances(): void
    {
        $production = (new Production())->setTitle('Neue Eigenproduktion');
        $event = (new ProductionEvent())->setDate(new \DateTime('2027-03-15'))->setTimeFrom('20:00')->setEventIndex(4);
        $event->addCategory((new EventCategory())->setName('Premiere')->setSlug('premiere-event'));
        $event->addCategory((new EventCategory())->setName('Ausverkauft')->setSlug('ausverkauft-event'));
        $production->addEvent($event);

        $result = $this->service([
            ['success' => true, 'action' => 'created', 'external_id' => 3145],
            ['success' => true, 'action' => 'created', 'event_index' => 0],
        ])->pushProduction($production);

        self::assertSame('success', $result['status']);
        self::assertSame(3145, $production->getExternalId());
        self::assertSame(0, $event->getEventIndex());

        [$create, $createEvent] = $this->requests;
        self::assertSame('PUT', $create['method']);
        self::assertSame('https://wp.test/wp-json/brux-crud/v1/production', $create['url']);
        self::assertSame('Basic ' . base64_encode('brux_api_crud:app pass word'), $create['auth']);
        self::assertNull($create['body']['external_id']);
        self::assertSame('Neue Eigenproduktion', $create['body']['title']);
        self::assertSame('draft', $create['body']['post_status']);
        self::assertArrayNotHasKey('events', $create['body']);

        self::assertSame(3145, $createEvent['body']['production_external_id']);
        self::assertNull($createEvent['body']['event_index'], 'a local index means nothing to WordPress');
        self::assertSame('2027-03-15', $createEvent['body']['date']);
        self::assertSame(['premiere-event', 'ausverkauft-event'], $createEvent['body']['categories'], 'sold out can be set by hand');
        self::assertArrayNotHasKey('incoming_total', $createEvent['body']);
        self::assertArrayNotHasKey('free_seats', $createEvent['body']);
        self::assertArrayNotHasKey('reservations', $createEvent['body']);
    }

    public function testWordPressProductionOnlyCarriesFieldsOwnedHere(): void
    {
        $production = (new Production())->setTitle('Aus WordPress')->setExternalId(1977);

        $this->service([['success' => true, 'action' => 'updated', 'external_id' => 1977]])->pushProduction($production);

        $body = $this->requests[0]['body'];
        self::assertSame(1977, $body['external_id']);
        self::assertArrayHasKey('contact_persons', $body);
        self::assertArrayHasKey('needs_sound_technician', $body);
        self::assertArrayNotHasKey('title', $body);
        self::assertArrayNotHasKey('prices', $body);
        self::assertArrayNotHasKey('content_html', $body);
    }

    public function testWordPressPerformanceOnlyCarriesFieldsOwnedHere(): void
    {
        $production = (new Production())->setTitle('Aus WordPress')->setExternalId(1977);
        $event = (new ProductionEvent())->setDate(new \DateTime('2027-03-15'))->setEventIndex(2);
        $production->addEvent($event);

        $this->service([['success' => true]])->pushProductionEvent($event);

        $body = $this->requests[0]['body'];
        self::assertSame(2, $body['event_index']);
        self::assertSame(1977, $body['production_external_id']);
        self::assertArrayNotHasKey('date', $body);
        self::assertArrayNotHasKey('categories', $body);
        self::assertArrayNotHasKey('prices', $body);
    }

    public function testDeletedPerformanceRenumbersTheLaterOnes(): void
    {
        $production = (new Production())->setTitle('Aus WordPress')->setExternalId(1977);
        $events = [];
        foreach ([0, 1, 2] as $index) {
            $production->addEvent($events[] = (new ProductionEvent())->setEventIndex($index));
        }

        $result = $this->service([['success' => true]])->deleteProductionEvent($events[1]);

        self::assertSame('success', $result['status']);
        self::assertSame('DELETE', $this->requests[0]['method']);
        self::assertSame('https://wp.test/wp-json/brux-crud/v1/productions/1977/events/1', $this->requests[0]['url']);
        self::assertSame([0, null, 1], array_map(static fn ($e) => $e->getEventIndex(), $events));
    }

    public function testRejectedDeleteKeepsTheNumbering(): void
    {
        $production = (new Production())->setTitle('Aus WordPress')->setExternalId(1977);
        $production->addEvent($first = (new ProductionEvent())->setEventIndex(0));
        $production->addEvent($second = (new ProductionEvent())->setEventIndex(1));

        $result = $this->service([new MockResponse(json_encode(['message' => 'Reservierungen vorhanden']), ['http_code' => 409])])
            ->deleteProductionEvent($first);

        self::assertSame('error', $result['status']);
        self::assertSame([0, 1], [$first->getEventIndex(), $second->getEventIndex()]);
    }

    public function testImageFromTheMediaLibraryIsNotSentAgain(): void
    {
        // new productions carry all fields, so only the origin of the image decides
        $fromWordPress = (new Production())->setTitle('Bild aus der Mediathek')->setPostThumbnailUrl('https://wp.test/wp-content/uploads/2026/04/bild.jpg');
        $uploadedHere = (new Production())->setTitle('Hier hochgeladen')->setPostThumbnailUrl('https://brux.test/uploads/productions/bild.jpg');

        $this->service([['success' => true, 'external_id' => 1977]])->pushProduction($fromWordPress);
        $this->service([['success' => true, 'external_id' => 1978]])->pushProduction($uploadedHere);

        self::assertArrayNotHasKey('post_thumbnail_url', $this->requests[0]['body']);
        self::assertSame('https://brux.test/uploads/productions/bild.jpg', $this->requests[1]['body']['post_thumbnail_url']);
    }

    public function testCategoryCarriesItsColourInTheDescription(): void
    {
        $category = (new ProductionCategory())->setName('Kinderprogramm')->setSlug('kinderprogramm')->setColor('#4caf50');

        $result = $this->service([['success' => true, 'action' => 'created', 'category' => ['term_id' => 32, 'slug' => 'kinderprogramm']]])->pushCategory($category);

        self::assertSame('success', $result['status']);
        self::assertSame(32, $category->getExternalId());
        self::assertSame('PUT', $this->requests[0]['method']);
        self::assertSame('https://wp.test/wp-json/brux-crud/v1/category', $this->requests[0]['url']);
        self::assertSame(['slug' => 'kinderprogramm', 'name' => 'Kinderprogramm', 'description' => '#4caf50'], $this->requests[0]['body']);

        $this->service([['success' => true, 'category' => ['term_id' => 32]]])->pushCategory($category->setColor(null));
        self::assertSame('', $this->requests[1]['body']['description'], 'no colour clears it on the website');
    }

    public function testRejectionIsReportedAndRecorded(): void
    {
        $production = (new Production())->setTitle('Aus WordPress')->setExternalId(1977);

        $result = $this->service([new MockResponse(json_encode(['code' => 'brux_conflict', 'message' => 'Konflikt']), ['http_code' => 409])])
            ->pushProduction($production);

        self::assertSame('error', $result['status']);
        self::assertSame('Konflikt', $result['message']);
        self::assertSame('Konflikt', $production->getLastPushError());
    }

    public function testNothingIsSentWithoutCredentials(): void
    {
        $service = new WordPressPushService(
            new MockHttpClient(fn () => self::fail('no request expected')),
            $this->createMock(EntityManagerInterface::class),
            new FieldOwnership([]),
            new NullLogger(),
            'https://wp.test/wp-json/brux-crud/v1',
            'brux_api_crud',
            '',
        );

        self::assertFalse($service->isConfigured());
        $production = new Production();
        self::assertSame('skipped', $service->pushProduction($production)['status']);
        self::assertSame(WordPressPushService::PENDING, $production->getLastPushError(), 'retried with the next import once set up');

        // what is on the website cannot be deleted yet, the import would bring it back
        $fromWordPress = (new Production())->setTitle('Aus WordPress')->setExternalId(1977);
        self::assertSame('error', $service->deleteProduction($fromWordPress)['status']);
        self::assertSame('skipped', $service->deleteProduction(new Production())['status'], 'local ones can go');
    }

    /**
     * @param list<array|MockResponse> $responses
     */
    private function service(array $responses): WordPressPushService
    {
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $headers = [];
            foreach ($options['normalized_headers'] ?? [] as $name => $lines) {
                $headers[$name] = substr($lines[0], \strlen($name) + 2);
            }
            $this->requests[] = [
                'method' => $method,
                'url' => $url,
                'body' => isset($options['body']) && \is_string($options['body']) ? json_decode($options['body'], true) : null,
                'auth' => $headers['authorization'] ?? null,
            ];
            $next = array_shift($responses);

            return $next instanceof MockResponse ? $next : new MockResponse(json_encode($next), ['http_code' => 200]);
        });

        return new WordPressPushService(
            $client,
            $this->createMock(EntityManagerInterface::class),
            new FieldOwnership([
                'production' => ['title', 'permalink', 'postThumbnailUrl', 'contentHtml', 'excerptHtml', 'priceList'],
                'production_event' => ['date', 'timeFrom', 'timeTo', 'room', 'status', 'reservationStatus', 'quota', 'incomingTotal', 'freeSeats', 'reservationNote', 'reservations', 'categories', 'priceList'],
            ]),
            new NullLogger(),
            'https://wp.test/wp-json/brux-crud/v1/',
            'brux_api_crud',
            'app pass word',
        );
    }
}
