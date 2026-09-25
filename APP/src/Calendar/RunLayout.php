<?php

namespace App\Calendar;

/**
 * Joins entries that belong together across consecutive days into one bar:
 * multi-day entries, and the same production/title in the same room on
 * back-to-back days (e.g. a run of performances).
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
        $candidates = [];
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
            $index = $indices[0];
            if ($entry->cancelled || $count === 1) {
                $perDay[$index][] = $entry;
                continue;
            }
            $key = $entry->colorKey . '|' . $entry->title . '|' . ($entry->roomId ?? '-');
            if (isset($candidates[$key][$index])) {
                // second entry of the same kind on the same day stays on its own
                $perDay[$index][] = $entry;
                continue;
            }
            $candidates[$key][$index] = $entry;
        }

        foreach ($candidates as $byDay) {
            ksort($byDay);
            $chain = [];
            $previous = null;
            foreach ($byDay as $index => $entry) {
                if ($previous !== null && $index !== $previous + 1) {
                    self::flush($chain, $days, $runs, $perDay);
                    $chain = [];
                }
                $chain[$index] = $entry;
                $previous = $index;
            }
            self::flush($chain, $days, $runs, $perDay);
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

    /**
     * @param array<int, CalendarEntry> $chain
     */
    private static function flush(array $chain, array $days, array &$runs, array &$perDay): void
    {
        if (\count($chain) >= 2) {
            $segments = [];
            foreach ($chain as $index => $entry) {
                $segments[] = ['day' => $days[$index], 'entry' => $entry, 'index' => $index];
            }
            $runs[] = self::run($segments);

            return;
        }
        foreach ($chain as $index => $entry) {
            $perDay[$index][] = $entry;
        }
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
