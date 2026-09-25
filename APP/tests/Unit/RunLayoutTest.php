<?php

namespace App\Tests\Unit;

use App\Calendar\CalendarEntry;
use App\Calendar\EntryGroup;
use App\Calendar\RunLayout;
use PHPUnit\Framework\TestCase;

final class RunLayoutTest extends TestCase
{
    /** @return \DateTimeImmutable[] */
    private function week(): array
    {
        $days = [];
        for ($i = 0; $i < 7; ++$i) {
            $days[] = new \DateTimeImmutable(sprintf('2026-11-%02d', 2 + $i));
        }

        return $days;
    }

    private function performance(string $panel, string $title, string $date, bool $cancelled = false): CalendarEntry
    {
        $start = new \DateTimeImmutable($date . ' 18:00');

        return new CalendarEntry($panel, EntryGroup::PERFORMANCE, 'performance', $title, $start, $start->modify('+2 hours'), roomId: 1, cancelled: $cancelled);
    }

    public function testConsecutiveDaysOfTheSameProductionAreJoined(): void
    {
        $result = RunLayout::arrange($this->week(), [
            $this->performance('pe-1', 'Tintinnabulum', '2026-11-06'),
            $this->performance('pe-2', 'Tintinnabulum', '2026-11-07'),
            $this->performance('pe-3', 'Tintinnabulum', '2026-11-10'),
            $this->performance('pe-4', 'Anderes', '2026-11-08'),
        ]);

        self::assertCount(1, $result['runs']);
        self::assertSame(4, $result['runs'][0]['col']);
        self::assertSame(2, $result['runs'][0]['span']);
        self::assertSame(['pe-1', 'pe-2'], array_map(static fn ($s) => $s['entry']->panel, $result['runs'][0]['segments']));
        self::assertSame('pe-4', $result['days'][6][0]->panel);
    }

    public function testCancelledDaysBreakTheRun(): void
    {
        $result = RunLayout::arrange($this->week(), [
            $this->performance('pe-1', 'Reinigung', '2026-11-03'),
            $this->performance('pe-2', 'Reinigung', '2026-11-04', cancelled: true),
            $this->performance('pe-3', 'Reinigung', '2026-11-05'),
        ]);

        self::assertSame([], $result['runs']);
        self::assertCount(1, $result['days'][1]);
        self::assertCount(1, $result['days'][2]);
    }

    public function testMultiDayEntryBecomesOneRun(): void
    {
        $start = new \DateTimeImmutable('2026-11-03 10:00');
        $festival = new CalendarEntry('a-9', EntryGroup::EVENT, 'closed_event', 'Festival', $start, $start->modify('+2 days'));

        $result = RunLayout::arrange($this->week(), [$festival]);

        self::assertSame(1, $result['runs'][0]['col']);
        self::assertSame(3, $result['runs'][0]['span']);
    }
}
