<?php

namespace App\Tests\Unit;

use App\Entity\CleaningException;
use App\Entity\CleaningSchedule;
use PHPUnit\Framework\TestCase;

final class CleaningScheduleTest extends TestCase
{
    private function schedule(): CleaningSchedule
    {
        return (new CleaningSchedule())
            ->setWeekdays([4, 1])
            ->setTimeFrom(new \DateTime('17:30'))
            ->setTimeTo(new \DateTime('18:30'))
            ->setActiveFrom(new \DateTime('2026-09-01'))
            ->setActiveTo(new \DateTime('2026-12-31'));
    }

    public function testOccursOnConfiguredWeekdaysWithinActivePeriod(): void
    {
        $schedule = $this->schedule();

        self::assertSame([1, 4], $schedule->getWeekdays());
        self::assertTrue($schedule->occursOn(new \DateTimeImmutable('2026-10-15')));
        self::assertFalse($schedule->occursOn(new \DateTimeImmutable('2026-10-16')));
        self::assertFalse($schedule->occursOn(new \DateTimeImmutable('2026-08-31')));
        self::assertFalse($schedule->occursOn(new \DateTimeImmutable('2027-01-04')));
        self::assertSame('2026-10-15 17:30', $schedule->startOn(new \DateTimeImmutable('2026-10-15'))->format('Y-m-d H:i'));
    }

    public function testCancellationAppliesToOneDayOnly(): void
    {
        $schedule = $this->schedule();
        $schedule->getExceptions()->add(new CleaningException($schedule, new \DateTimeImmutable('2026-10-15 13:00')));

        self::assertTrue($schedule->isCancelledOn(new \DateTimeImmutable('2026-10-15')));
        self::assertFalse($schedule->isCancelledOn(new \DateTimeImmutable('2026-10-19')));
    }
}
