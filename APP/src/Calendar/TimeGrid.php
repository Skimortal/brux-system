<?php

namespace App\Calendar;

/**
 * Lays out entries on a time grid: one column per room (day view) or per day (week
 * view), hours running down. Timed entries are placed by start and duration; entries
 * that overlap in a column sit side by side. All-day and multi-day entries go into
 * the strip above the grid. A late show past midnight stays in the column of its
 * evening and is cut at midnight.
 */
final class TimeGrid
{
    /** Hours shown at least, widened when entries start earlier or end later. */
    private const FIRST_HOUR = 8;
    private const LAST_HOUR = 23;

    /**
     * @param array<int, array{key: string, label: string, date: \DateTimeImmutable, roomId: ?int, house: bool}> $columns
     *        house: the column for entries without a room
     * @param CalendarEntry[] $entries
     *
     * @return array{
     *     from: int, to: int, hours: int[],
     *     columns: array<int, array{key: string, label: string, date: \DateTimeImmutable, roomId: ?int,
     *         allDay: CalendarEntry[], timed: array<int, array{entry: CalendarEntry, top: float, height: float, left: float, width: float}>}>
     * }
     */
    public static function arrange(array $columns, array $entries): array
    {
        $byRoom = array_filter($columns, static fn (array $c) => $c['roomId'] !== null || $c['house']) !== [];
        $result = [];
        $earliest = self::FIRST_HOUR * 60;
        $latest = self::LAST_HOUR * 60 + 60;

        foreach ($columns as $column) {
            $day = $column['date']->setTime(0, 0);
            $next = $day->modify('+1 day');
            $allDay = [];
            $timed = [];
            foreach ($entries as $entry) {
                if ($byRoom && !self::inColumn($entry, $column)) {
                    continue;
                }
                if ($entry->allDay || $entry->end->getTimestamp() - $entry->start->getTimestamp() >= 86400) {
                    if ($entry->overlapsDay($day)) {
                        $allDay[] = $entry;
                    }
                    continue;
                }
                if ($entry->start < $day || $entry->start >= $next) {
                    continue;
                }
                $start = (int) (($entry->start->getTimestamp() - $day->getTimestamp()) / 60);
                $end = (int) min(24 * 60, ($entry->end->getTimestamp() - $day->getTimestamp()) / 60);
                $timed[] = ['entry' => $entry, 'start' => $start, 'end' => max($end, $start + 15)];
                $earliest = min($earliest, intdiv($start, 60) * 60);
                $latest = max($latest, (int) ceil($end / 60) * 60);
            }
            $result[] = $column + ['allDay' => $allDay, 'timed' => self::sideBySide($timed)];
        }

        $span = max(60, $latest - $earliest);
        foreach ($result as &$column) {
            foreach ($column['timed'] as &$item) {
                $item['top'] = round(($item['start'] - $earliest) / $span * 100, 3);
                $item['height'] = round(($item['end'] - $item['start']) / $span * 100, 3);
                unset($item['start'], $item['end']);
            }
            unset($item);
        }
        unset($column);

        return [
            'from' => $earliest,
            'to' => $earliest + $span,
            'hours' => range(intdiv($earliest, 60), intdiv($earliest + $span, 60) - 1),
            'columns' => $result,
        ];
    }

    /** @param array{roomId: ?int, house: bool} $column */
    private static function inColumn(CalendarEntry $entry, array $column): bool
    {
        if ($column['house']) {
            return $entry->roomId === null;
        }

        return $entry->roomId === $column['roomId'];
    }

    /**
     * Gives overlapping entries a slot each; a group of entries that overlap one
     * another shares the column width.
     *
     * @param array<int, array{entry: CalendarEntry, start: int, end: int}> $items
     *
     * @return array<int, array{entry: CalendarEntry, start: int, end: int, left: float, width: float}>
     */
    private static function sideBySide(array $items): array
    {
        usort($items, static fn ($a, $b) => [$a['start'], -$a['end']] <=> [$b['start'], -$b['end']]);
        $placed = [];
        $group = [];
        $slotEnds = [];
        $groupEnd = -1;
        $flush = static function () use (&$group, &$placed, &$slotEnds): void {
            $count = max(1, \count($slotEnds));
            foreach ($group as $item) {
                $item['width'] = round(100 / $count, 3);
                $item['left'] = round($item['slot'] * 100 / $count, 3);
                unset($item['slot']);
                $placed[] = $item;
            }
            $group = [];
            $slotEnds = [];
        };

        foreach ($items as $item) {
            if ($group !== [] && $item['start'] >= $groupEnd) {
                $flush();
            }
            $groupEnd = $group === [] ? $item['end'] : max($groupEnd, $item['end']);
            $slot = 0;
            while (isset($slotEnds[$slot]) && $slotEnds[$slot] > $item['start']) {
                ++$slot;
            }
            $slotEnds[$slot] = $item['end'];
            $item['slot'] = $slot;
            $group[] = $item;
        }
        if ($group !== []) {
            $flush();
        }

        return $placed;
    }
}
