<?php

namespace App\Tests\Unit;

use App\Entity\ProductionEvent;
use PHPUnit\Framework\TestCase;

final class ProductionEventTest extends TestCase
{
    public function testTimesFromWordPressStrings(): void
    {
        $event = (new ProductionEvent())->setDate(new \DateTime('2026-10-16'))->setTimeFrom('20:00')->setTimeTo('21:45');

        self::assertSame('2026-10-16 20:00', $event->getStartsAt()?->format('Y-m-d H:i'));
        self::assertSame('2026-10-16 21:45', $event->getEndsAt()?->format('Y-m-d H:i'));
    }

    public function testMissingEndDefaultsToTwoHours(): void
    {
        $event = (new ProductionEvent())->setDate(new \DateTime('2026-10-16'))->setTimeFrom('20:00');

        self::assertSame('22:00', $event->getEndsAt()?->format('H:i'));
    }

    public function testWithoutTimesIsAllDay(): void
    {
        $event = (new ProductionEvent())->setDate(new \DateTime('2026-10-16'));

        self::assertTrue($event->isAllDay());
        self::assertSame('2026-10-17 00:00', $event->getEndsAt()?->format('Y-m-d H:i'));
    }

    public function testSeatsAreDerivedWhenPartial(): void
    {
        $event = (new ProductionEvent())->setQuota(70)->setIncomingTotal(46);

        self::assertSame(24, $event->getSeatsFree());
        self::assertTrue((new ProductionEvent())->setReservationNote(' https://example.test/ ')->hasReservationLink());
    }

    public function testQuotaAloneMeansNothingBooked(): void
    {
        $event = (new ProductionEvent())->setQuota(50);

        self::assertSame(0, $event->getSeatsTaken());
        self::assertSame(50, $event->getSeatsFree());
        self::assertNull((new ProductionEvent())->getSeatsFree());
    }
}
