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

    public function testConsecutiveDaysOfTheSameProductionStaySeparate(): void
    {
        $result = RunLayout::arrange($this->week(), [
            $this->performance('pe-1', 'Tintinnabulum', '2026-11-06'),
            $this->performance('pe-2', 'Tintinnabulum', '2026-11-07'),
            $this->performance('pe-4', 'Anderes', '2026-11-08'),
        ]);

        self::assertSame([], $result['runs']);
        self::assertSame('pe-1', $result['days'][4][0]->panel);
        self::assertSame('pe-2', $result['days'][5][0]->panel);
        self::assertSame('pe-4', $result['days'][6][0]->panel);
    }

    public function testLateShowPastMidnightStaysOnItsEvening(): void
    {
        $start = new \DateTimeImmutable('2026-11-04 22:30');
        $late = new CalendarEntry('pe-9', EntryGroup::PERFORMANCE, 'performance', 'Spät', $start, $start->modify('+2 hours'), roomId: 1);

        $result = RunLayout::arrange($this->week(), [$late]);

        self::assertSame([], $result['runs']);
        self::assertSame('pe-9', $result['days'][2][0]->panel);
        self::assertSame([], $result['days'][3]);
        self::assertSame('22:30–00:30', $late->timeLabelOn($this->week()[2]));
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
