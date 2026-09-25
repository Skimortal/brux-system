<?php

namespace App\Calendar;

/**
 * One item in the calendar, independent of its source.
 */
final class CalendarEntry
{
    public ?string $conflict = null;

    public function __construct(
        public readonly string $panel,
        public readonly EntryGroup $group,
        public readonly string $colorKey,
        public readonly string $title,
        public readonly \DateTimeImmutable $start,
        public readonly \DateTimeImmutable $end,
        public readonly bool $allDay = false,
        public readonly ?int $roomId = null,
        public readonly ?string $meta = null,
        public readonly bool $fromWordPress = false,
        public readonly bool $cancelled = false,
        public readonly ?string $icon = null,
    ) {
    }

    public function isPast(\DateTimeInterface $now): bool
    {
        return $this->end <= $now;
    }

    public function overlapsDay(\DateTimeImmutable $day): bool
    {
        $next = $day->modify('+1 day');

        return $this->start < $next && $this->end > $day;
    }

    public function timeLabel(): string
    {
        if ($this->allDay) {
            return 'ganztägig';
        }
        if ($this->start->format('Y-m-d') !== $this->end->modify('-1 second')->format('Y-m-d')) {
            return $this->start->format('d.m. H:i') . ' – ' . $this->end->format('d.m. H:i');
        }

        return $this->start->format('H:i') . '–' . $this->end->format('H:i');
    }

    /** Time label for a single day of a multi-day entry. */
    public function timeLabelOn(\DateTimeImmutable $day): string
    {
        if ($this->allDay) {
            return 'ganztägig';
        }
        $next = $day->modify('+1 day');
        $startsBefore = $this->start < $day;
        $endsAfter = $this->end > $next;
        if (!$startsBefore && !$endsAfter) {
            return $this->start->format('H:i') . '–' . $this->end->format('H:i');
        }
        if ($startsBefore && $endsAfter) {
            return 'ganztägig';
        }

        return $startsBefore ? 'bis ' . $this->end->format('H:i') : 'ab ' . $this->start->format('H:i');
    }
}
