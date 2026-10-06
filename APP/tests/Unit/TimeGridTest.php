<?php

namespace App\Tests\Unit;

use App\Calendar\CalendarEntry;
use App\Calendar\EntryGroup;
use App\Calendar\TimeGrid;
use PHPUnit\Framework\TestCase;

final class TimeGridTest extends TestCase
{
    private function entry(string $panel, string $start, string $end, ?int $room = 1, bool $allDay = false): CalendarEntry
    {
        return new CalendarEntry($panel, EntryGroup::PERFORMANCE, 'performance', $panel, new \DateTimeImmutable($start), new \DateTimeImmutable($end), allDay: $allDay, roomId: $room);
    }

    /** @return array<int, array{key: string, label: string, date: \DateTimeImmutable, roomId: ?int, house: bool}> */
    private function rooms(): array
    {
        $day = new \DateTimeImmutable('2026-10-27');

        return [
            ['key' => 'room-1', 'label' => 'Schwarz', 'date' => $day, 'roomId' => 1, 'house' => false],
            ['key' => 'room-2', 'label' => 'Weiß', 'date' => $day, 'roomId' => 2, 'house' => false],
            ['key' => 'house', 'label' => 'Ganzes Haus', 'date' => $day, 'roomId' => null, 'house' => true],
        ];
    }

    public function testEntriesArePlacedByTimeInTheirRoom(): void
    {
        $grid = TimeGrid::arrange($this->rooms(), [
            $this->entry('a', '2026-10-27 10:00', '2026-10-27 12:00', 1),
            $this->entry('b', '2026-10-27 20:00', '2026-10-27 22:00', 2),
            $this->entry('c', '2026-10-27 09:00', '2026-10-27 10:00', null),
        ]);

        // default range 08:00–24:00
        self::assertSame(8 * 60, $grid['from']);
        self::assertSame(24 * 60, $grid['to']);
        [$black, $white, $house] = $grid['columns'];
        self::assertSame('a', $black['timed'][0]['entry']->panel);
        self::assertEqualsWithDelta(12.5, $black['timed'][0]['top'], 0.01);
        self::assertEqualsWithDelta(12.5, $black['timed'][0]['height'], 0.01);
        self::assertSame('b', $white['timed'][0]['entry']->panel);
        self::assertSame('c', $house['timed'][0]['entry']->panel);
    }

    public function testOverlappingEntriesSitSideBySide(): void
    {
        $grid = TimeGrid::arrange($this->rooms(), [
            $this->entry('a', '2026-10-27 10:00', '2026-10-27 12:00'),
            $this->entry('b', '2026-10-27 11:00', '2026-10-27 13:00'),
            $this->entry('c', '2026-10-27 14:00', '2026-10-27 15:00'),
        ]);

        $byPanel = [];
        foreach ($grid['columns'][0]['timed'] as $item) {
            $byPanel[$item['entry']->panel] = [$item['left'], $item['width']];
        }
        self::assertSame([0.0, 50.0], $byPanel['a']);
        self::assertSame([50.0, 50.0], $byPanel['b']);
        self::assertSame([0.0, 100.0], $byPanel['c']);
    }

    public function testLateShowWidensTheRangeAndAllDayGoesOnTop(): void
    {
        $grid = TimeGrid::arrange($this->rooms(), [
            $this->entry('late', '2026-10-27 22:30', '2026-10-28 00:30'),
            $this->entry('day', '2026-10-27 00:00', '2026-10-28 00:00', 1, true),
            $this->entry('early', '2026-10-27 06:30', '2026-10-27 07:00'),
        ]);

        self::assertSame(6 * 60, $grid['from']);
        self::assertSame(24 * 60, $grid['to'], 'cut at midnight');
        self::assertSame('day', $grid['columns'][0]['allDay'][0]->panel);
        self::assertCount(2, $grid['columns'][0]['timed']);
    }

    public function testWeekColumnsTakeEveryRoom(): void
    {
        $days = [];
        foreach (['2026-10-26', '2026-10-27'] as $i => $date) {
            $days[] = ['key' => 'day-' . $i, 'label' => $date, 'date' => new \DateTimeImmutable($date), 'roomId' => null, 'house' => false];
        }

        $grid = TimeGrid::arrange($days, [
            $this->entry('a', '2026-10-27 10:00', '2026-10-27 12:00', 1),
            $this->entry('b', '2026-10-27 10:00', '2026-10-27 12:00', 2),
        ]);

        self::assertSame([], $grid['columns'][0]['timed']);
        self::assertCount(2, $grid['columns'][1]['timed']);
    }
}
