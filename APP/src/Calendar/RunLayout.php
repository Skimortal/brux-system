<?php

namespace App\Calendar;

/**
 * Lays out a row of days: an entry spanning several days becomes one bar, every
 * other entry stays on its day. Entries on consecutive days are not joined, so each
 * performance keeps its own place, colour and time order.
 */
final class RunLayout
{
    /**
     * @param \DateTimeImmutable[] $days    the days of one row (a week or a single day)
     * @param CalendarEntry[]      $entries entries to arrange
     *
     * @return array{
     *     runs: array<int, array{col: int, span: int, row: int, segments: array<int, array{day: \DateTimeImmutable, entry: CalendarEntry}>}>,
     *     rows: int,
     *     days: array<int, CalendarEntry[]>
     * }
     */
    public static function arrange(array $days, array $entries): array
    {
        $count = \count($days);
        $perDay = array_fill(0, $count, []);
        $runs = [];

        foreach ($entries as $entry) {
            $indices = [];
            foreach ($days as $i => $day) {
                if ($entry->overlapsDay($day)) {
                    $indices[] = $i;
                }
            }
            if ($indices === []) {
                continue;
            }
            if (\count($indices) > 1 && $count > 1) {
                $runs[] = self::run(array_map(static fn (int $i) => ['day' => $days[$i], 'entry' => $entry, 'index' => $i], $indices), true);
                continue;
            }
            $perDay[$indices[0]][] = $entry;
        }

        // pack runs into rows
        usort($runs, static fn ($a, $b) => [$a['col'], -$a['span']] <=> [$b['col'], -$b['span']]);
        $rowEnds = [];
        foreach ($runs as &$run) {
            $row = 0;
            while (isset($rowEnds[$row]) && $rowEnds[$row] >= $run['col']) {
                ++$row;
            }
            $rowEnds[$row] = $run['col'] + $run['span'] - 1;
            $run['row'] = $row;
        }
        unset($run);

        return ['runs' => $runs, 'rows' => \count($rowEnds), 'days' => $perDay];
    }

    private static function run(array $segments, bool $single = false): array
    {
        $first = $segments[0]['index'];

        return [
            'col' => $first,
            'span' => \count($segments),
            'row' => 0,
            // one entry spanning several days (rather than several entries on consecutive days)
            'single' => $single,
            'segments' => array_map(static fn ($s) => ['day' => $s['day'], 'entry' => $s['entry']], $segments),
        ];
    }
}
