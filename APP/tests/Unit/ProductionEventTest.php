<?php

namespace App\Tests\Unit;

use App\Entity\EventPrice;
use App\Entity\Production;
use App\Entity\ProductionEvent;
use App\Entity\ProductionPrice;
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

    public function testPricesFollowTheProduction(): void
    {
        $production = new Production();
        foreach ([['Kulturpass', '0', 5], ['Freikarte', '0', 0], ['Stuhl', '16', 60]] as $index => [$category, $price, $seats]) {
            $production->addPriceList((new ProductionPrice())->setPriceIndex($index)->setCategoryLabel($category)->setPriceLabel($price)->setParentReserved($seats));
        }
        $event = (new ProductionEvent())->setProduction($production);
        $event->addPriceList((new EventPrice())->setPriceIndex(2)->setCategoryLabel('Alt')->setPriceLabel('99')->setReservedSeats(40));
        $event->addPriceList((new EventPrice())->setPriceIndex(7)->setCategoryLabel('Weg')->setReservedSeats(1));

        $event->alignPricesWithProduction();

        $rows = array_map(static fn (EventPrice $p) => [$p->getPriceIndex(), $p->getCategoryLabel(), $p->getPriceLabel(), $p->getReservedSeats()], array_values($event->getPriceList()->toArray()));
        usort($rows, static fn ($a, $b) => $a[0] <=> $b[0]);
        self::assertSame([[0, 'Kulturpass', '0', 5], [1, 'Freikarte', '0', 0], [2, 'Stuhl', '16', 40]], $rows);
    }
}
