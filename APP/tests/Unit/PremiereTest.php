<?php

namespace App\Tests\Unit;

use App\Entity\Production;
use App\Entity\ProductionEvent;
use App\Enum\EventStatus;
use PHPUnit\Framework\TestCase;

final class PremiereTest extends TestCase
{
    public function testFirstPerformanceIsThePremiereWithoutAMark(): void
    {
        [$production, $first, $second] = $this->production();

        self::assertTrue($production->isPremiere($first));
        self::assertFalse($production->isPremiere($second));
    }

    public function testMarkedPerformancesAreThePremieres(): void
    {
        [$production, $first, $second] = $this->production();
        $second->setPremiere(true);

        self::assertFalse($production->isPremiere($first));
        self::assertTrue($production->isPremiere($second));
    }

    public function testCancelledFirstPerformanceIsSkipped(): void
    {
        [$production, $first, $second] = $this->production();
        $first->setStatus(EventStatus::INACTIVE);

        self::assertTrue($production->isPremiere($second));
    }

    /** @return array{Production, ProductionEvent, ProductionEvent} */
    private function production(): array
    {
        $production = (new Production())->setTitle('Stück');
        $second = (new ProductionEvent())->setDate(new \DateTime('2027-03-16'))->setTimeFrom('20:00');
        $first = (new ProductionEvent())->setDate(new \DateTime('2027-03-15'))->setTimeFrom('20:00');
        $production->addEvent($second);
        $production->addEvent($first);

        return [$production, $first, $second];
    }
}
