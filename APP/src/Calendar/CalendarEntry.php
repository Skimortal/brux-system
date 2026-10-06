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
        public readonly bool $cancelled = false,
        public readonly ?string $icon = null,
        /** Rooms an entry belongs to when it has several (key loans: the rooms the key opens). */
        public readonly ?array $roomIds = null,
        /** Colour of the production's category, shown as the entry background. */
        public readonly ?string $color = null,
        /** Whether the entry can be dragged to another day or room (performances only). */
        public readonly bool $movable = false,
        /** Reserved seats, to warn before moving a performance people have booked. */
        public readonly ?int $reservedSeats = null,
        /** A page to open instead of a side panel. */
        public readonly ?string $url = null,
    ) {
    }

    /** Whether the entry shows up in the calendar of the given room. Entries without a room show up everywhere. */
    public function belongsToRoom(int $roomId): bool
    {
        if ($this->roomIds !== null) {
            return \in_array($roomId, $this->roomIds, true);
        }

        return $this->roomId === null || $this->roomId === $roomId;
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
        // a late show past midnight is shown on its evening with its real end
        $shortOvernight = !$startsBefore && $endsAfter && $this->end->getTimestamp() - $this->start->getTimestamp() < 86400;
        if ((!$startsBefore && !$endsAfter) || $shortOvernight) {
            return $this->start->format('H:i') . '–' . $this->end->format('H:i');
        }
        if ($startsBefore && $endsAfter) {
            return 'ganztägig';
        }

        return $startsBefore ? 'bis ' . $this->end->format('H:i') : 'ab ' . $this->start->format('H:i');
    }
}
