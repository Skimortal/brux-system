<?php

namespace App\Calendar;

/**
 * Everything the calendar template needs for one range.
 */
final class CalendarView
{
    /**
     * @param \DateTimeImmutable[] $days
     * @param array<int, array{key: string, label: string, sub: ?string, days: array<int, CalendarEntry[]>}> $lanes
     * @param array<int, array{loan: \App\Entity\KeyLoan, col: int, span: int, row: int, label: string, state: string, clippedStart: bool, clippedEnd: bool}> $keyBars
     * @param CalendarEntry[] $entries
     */
    public function __construct(
        public readonly CalendarQuery $query,
        public readonly array $days,
        public readonly array $lanes,
        public readonly array $keyBars,
        public readonly int $keyRows,
        public readonly array $entries,
        public readonly \DateTimeImmutable $now,
        /** @var array<int, array{label: string, roomId: int, lane: ?array, weeks: ?array}> one calendar per room (layout "split") */
        public readonly array $split = [],
    ) {
    }

    public function title(): string
    {
        $formatter = new \IntlDateFormatter('de_AT', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'MMMM y');
        $first = $this->query->rangeStart();
        $last = $this->query->rangeEnd()->modify('-1 day');

        return match ($this->query->view) {
            'month' => (string) $formatter->format($this->query->date),
            'day' => (new \IntlDateFormatter('de_AT', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'EEEE, d. MMMM y'))->format($this->query->date),
            default => $first->format('m') === $last->format('m')
                ? $first->format('j.') . '–' . $last->format('j. ') . $formatter->format($last)
                : $first->format('j.n.') . ' – ' . $last->format('j.n.Y'),
        };
    }

    public function isToday(\DateTimeImmutable $day): bool
    {
        return $day->format('Y-m-d') === $this->now->format('Y-m-d');
    }

    /** @return array<int, \DateTimeImmutable[]> days grouped by week (month view) */
    public function weeks(): array
    {
        return array_chunk($this->days, 7);
    }

    /** @return CalendarEntry[] */
    public function entriesOn(\DateTimeImmutable $day): array
    {
        return array_values(array_filter($this->entries, static fn (CalendarEntry $e) => $e->overlapsDay($day)));
    }

    /**
     * Month view: one row per week with joined runs and the remaining entries per day.
     *
     * @return array<int, array{days: \DateTimeImmutable[], runs: array, rows: int, perDay: array<int, CalendarEntry[]>}>
     */
    public function monthWeeks(): array
    {
        $weeks = [];
        foreach ($this->weeks() as $weekDays) {
            $end = end($weekDays)->modify('+1 day');
            $entries = array_filter($this->entries, static fn (CalendarEntry $e) => $e->start < $end && $e->end > $weekDays[0]);
            $arranged = RunLayout::arrange($weekDays, array_values($entries));
            $weeks[] = ['days' => $weekDays, 'runs' => $arranged['runs'], 'rows' => $arranged['rows'], 'perDay' => $arranged['days']];
        }

        return $weeks;
    }

    public function inCurrentMonth(\DateTimeImmutable $day): bool
    {
        return $day->format('Y-m') === $this->query->date->format('Y-m');
    }
}
