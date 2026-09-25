<?php

namespace App\Calendar;

use App\Entity\Appointment;
use App\Entity\KeyLoan;
use App\Entity\ProductionEvent;
use App\Entity\Room;
use App\Enum\AppointmentTypeEnum;
use App\Repository\AppointmentRepository;
use App\Repository\CleaningScheduleRepository;
use App\Repository\KeyLoanRepository;
use App\Repository\ProductionEventRepository;
use App\Repository\RoomRepository;
use Symfony\Component\Clock\ClockInterface;

/**
 * Collects performances, appointments, cleaning schedule occurrences and key loans
 * for a date range and arranges them for the calendar page.
 */
final class CalendarBuilder
{
    public function __construct(
        private readonly AppointmentRepository $appointments,
        private readonly ProductionEventRepository $performances,
        private readonly CleaningScheduleRepository $cleaningSchedules,
        private readonly KeyLoanRepository $keyLoans,
        private readonly RoomRepository $rooms,
        private readonly ClockInterface $clock,
    ) {
    }

    public function build(CalendarQuery $query): CalendarView
    {
        $from = $query->rangeStart();
        $to = $query->rangeEnd();
        $now = $this->clock->now();

        $days = [];
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            $days[] = $day;
        }

        $entries = array_filter(
            $this->collectEntries($from, $to),
            static fn (CalendarEntry $e) => !$query->isHidden($e->group)
        );
        usort($entries, static fn (CalendarEntry $a, CalendarEntry $b) => [$a->allDay ? 0 : 1, $a->start] <=> [$b->allDay ? 0 : 1, $b->start]);
        $this->markConflicts($entries);

        [$bars, $rows] = $query->isHidden(EntryGroup::KEYS) ? [[], 0] : $this->keyBars($days, $from, $to, $now);

        return new CalendarView(
            $query,
            $days,
            $this->lanes($query, $days, $entries),
            $bars,
            $rows,
            array_values($entries),
            $now,
        );
    }

    /** @return CalendarEntry[] */
    public function collectEntries(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $entries = [];
        foreach ($this->performances->findBetween($from, $to) as $performance) {
            $entry = $this->performanceEntry($performance);
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }
        foreach ($this->appointments->findOverlapping($from, $to) as $appointment) {
            if ($appointment->getProductionEvent() !== null) {
                continue;
            }
            $entries[] = $this->appointmentEntry($appointment);
        }
        foreach ($this->cleaningSchedules->findActiveBetween($from, $to) as $schedule) {
            for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
                if (!$schedule->occursOn($day)) {
                    continue;
                }
                $cancelled = $schedule->isCancelledOn($day);
                $entries[] = new CalendarEntry(
                    panel: sprintf('cr-%d-%s', $schedule->getId(), $day->format('Ymd')),
                    group: EntryGroup::CLEANING,
                    colorKey: 'cleaning',
                    title: 'Reinigung',
                    start: $schedule->startOn($day),
                    end: $schedule->endOn($day),
                    meta: $cancelled ? 'abgesagt' : $schedule->getCleaningContact()?->getName() . ' · Plan',
                    cancelled: $cancelled,
                    icon: 'lucide:repeat',
                );
            }
        }

        return $entries;
    }

    private function performanceEntry(ProductionEvent $performance): ?CalendarEntry
    {
        $start = $performance->getStartsAt();
        $end = $performance->getEndsAt();
        if ($start === null || $end === null) {
            return null;
        }
        $meta = [];
        if ($performance->isCancelled()) {
            $meta[] = 'abgesagt';
        } elseif ($performance->isSoldOut()) {
            $meta[] = 'ausverkauft';
        }
        if ($performance->getQuota() !== null && $performance->getSeatsTaken() !== null && !$performance->isCancelled()) {
            $meta[] = sprintf('%d / %d reserviert', $performance->getSeatsTaken(), $performance->getQuota());
        }
        $staffing = $performance->getAppointment();
        if ($staffing !== null && ($technicianMeta = $this->technicianMeta($staffing)) !== null) {
            $meta[] = $technicianMeta;
        }

        return new CalendarEntry(
            panel: 'pe-' . $performance->getId(),
            group: EntryGroup::PERFORMANCE,
            colorKey: 'performance',
            title: (string) $performance->getProduction()?->getTitle(),
            start: $start,
            end: $end,
            allDay: $performance->isAllDay(),
            roomId: $performance->getRoom()?->getId(),
            meta: $meta !== [] ? implode(' · ', $meta) : null,
            fromWordPress: true,
            cancelled: $performance->isCancelled(),
        );
    }

    private function appointmentEntry(Appointment $appointment): CalendarEntry
    {
        $type = $appointment->getType();
        $meta = $this->technicianMeta($appointment);
        if ($meta === null && \count($appointment->getAppointmentVolunteers()) > 0) {
            $meta = 'Freiwillige ' . \count($appointment->getAppointmentVolunteers());
        }
        if ($type === AppointmentTypeEnum::CLEANING) {
            $areas = array_map(static fn ($a) => $a->label(), $appointment->getCleaningAreas());
            $meta = $areas !== [] ? implode(', ', $areas) : null;
        }
        if ($type === AppointmentTypeEnum::PRODUCTION && $appointment->getStatus() !== null) {
            $meta = trim($appointment->getStatus()->label() . ($meta !== null ? ' · ' . $meta : ''));
        }
        $start = \DateTimeImmutable::createFromInterface($appointment->getStartDate());
        $end = \DateTimeImmutable::createFromInterface($appointment->getEndDate());
        if ($appointment->isAllDay()) {
            $start = $start->setTime(0, 0);
            $end = $end->setTime(0, 0)->modify('+1 day');
        }

        return new CalendarEntry(
            panel: 'a-' . $appointment->getId(),
            group: EntryGroup::forAppointmentType($type),
            colorKey: $type->value,
            title: $appointment->getDisplayTitle(),
            start: $start,
            end: $end > $start ? $end : $start->modify('+30 minutes'),
            allDay: $appointment->isAllDay(),
            roomId: $appointment->getRoom()?->getId(),
            meta: $meta,
            icon: $appointment->getEventType()?->icon() ?? ($appointment->isInSeries() ? 'lucide:repeat' : null),
        );
    }

    private function technicianMeta(Appointment $appointment): ?string
    {
        $total = \count($appointment->getAppointmentTechnicians());
        if ($total === 0) {
            return null;
        }
        $confirmed = 0;
        foreach ($appointment->getAppointmentTechnicians() as $technician) {
            $confirmed += $technician->isConfirmed() ? 1 : 0;
        }

        return sprintf('Technik %d/%d%s', $confirmed, $total, $confirmed < $total ? ' offen' : '');
    }

    /**
     * Flags entries in the same room whose times overlap.
     *
     * @param CalendarEntry[] $entries sorted by start
     */
    private function markConflicts(array $entries): void
    {
        $byRoom = [];
        foreach ($entries as $entry) {
            if ($entry->roomId !== null && !$entry->cancelled && !$entry->allDay) {
                $byRoom[$entry->roomId][] = $entry;
            }
        }
        foreach ($byRoom as $roomEntries) {
            $count = \count($roomEntries);
            for ($i = 0; $i < $count; ++$i) {
                for ($j = $i + 1; $j < $count; ++$j) {
                    $a = $roomEntries[$i];
                    $b = $roomEntries[$j];
                    if ($b->start >= $a->end) {
                        break;
                    }
                    $a->conflict ??= sprintf('Überschneidung mit „%s“ (%s)', $b->title, $b->timeLabel());
                    $b->conflict ??= sprintf('Überschneidung mit „%s“ (%s)', $a->title, $a->timeLabel());
                }
            }
        }
    }

    /**
     * @param \DateTimeImmutable[] $days
     * @param CalendarEntry[]      $entries
     *
     * @return array<int, array{key: string, label: string, sub: ?string, days: array<int, CalendarEntry[]>}>
     */
    private function lanes(CalendarQuery $query, array $days, array $entries): array
    {
        if ($query->view === 'month') {
            return [];
        }
        $lanes = [];
        if ($query->layout === 'all') {
            $lanes['all'] = ['key' => 'all', 'label' => 'Alle Räume', 'sub' => null, 'roomId' => null, 'days' => []];
        } else {
            $lanes['house'] = ['key' => 'house', 'label' => 'Ganzes Haus', 'sub' => 'ohne Raum', 'roomId' => null, 'days' => []];
            $usedRooms = array_unique(array_filter(array_map(static fn (CalendarEntry $e) => $e->roomId, $entries)));
            /** @var Room $room */
            foreach ($this->rooms->findBy([], ['position' => 'ASC', 'name' => 'ASC']) as $room) {
                if ($room->isShowOnDashboard() || \in_array($room->getId(), $usedRooms, true)) {
                    $lanes['room-' . $room->getId()] = ['key' => 'room-' . $room->getId(), 'label' => (string) $room->getName(), 'sub' => null, 'roomId' => $room->getId(), 'days' => []];
                }
            }
        }
        foreach ($lanes as &$lane) {
            $lane['days'] = array_fill(0, \count($days), []);
        }
        unset($lane);

        $byLane = array_fill_keys(array_keys($lanes), []);
        foreach ($entries as $entry) {
            $laneKey = $query->layout === 'all' ? 'all' : ($entry->roomId !== null ? 'room-' . $entry->roomId : 'house');
            if (!isset($lanes[$laneKey])) {
                $laneKey = $query->layout === 'all' ? 'all' : 'house';
            }
            $byLane[$laneKey][] = $entry;
        }
        foreach ($lanes as $key => &$lane) {
            $arranged = RunLayout::arrange($days, $byLane[$key]);
            $lane['days'] = $arranged['days'];
            $lane['runs'] = $arranged['runs'];
            $lane['runRows'] = $arranged['rows'];
        }
        unset($lane);

        return array_values($lanes);
    }

    /**
     * Key loans as horizontal bars, packed into as few rows as possible.
     *
     * @param \DateTimeImmutable[] $days
     *
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function keyBars(array $days, \DateTimeImmutable $from, \DateTimeImmutable $to, \DateTimeImmutable $now): array
    {
        $bars = [];
        $rowEnds = [];
        $lastIndex = \count($days) - 1;
        foreach ($this->keyLoans->findOverlapping($from, $to) as $loan) {
            $start = $loan->getStartAt();
            $end = $loan->getReturnedAt() ?? $loan->getDueAt();
            $overdue = $loan->isOverdue($now);
            // keys that are still out (open-ended or overdue) stay visible until they come back
            if ($end === null || $overdue) {
                $end = $to;
            }
            $startIndex = max(0, (int) floor(($start->getTimestamp() - $from->getTimestamp()) / 86400));
            $endIndex = min($lastIndex, (int) floor(($end->getTimestamp() - 1 - $from->getTimestamp()) / 86400));
            if ($startIndex > $lastIndex || $endIndex < 0) {
                continue;
            }
            $endIndex = max($startIndex, $endIndex);

            $row = 0;
            while (isset($rowEnds[$row]) && $rowEnds[$row] >= $startIndex) {
                ++$row;
            }
            $rowEnds[$row] = $endIndex;

            $state = match (true) {
                $loan->isReturned() => 'returned',
                $overdue => 'overdue',
                $loan->isPlannedAt($now) => 'planned',
                default => 'active',
            };
            $label = sprintf('%s → %s', $loan->getKey()?->getName(), $loan->getHolderLabel());
            if ($state === 'overdue') {
                $label .= ' · überfällig seit ' . $loan->getDueAt()?->format('d.m.');
            } elseif ($loan->getDueAt() !== null && $state !== 'returned') {
                $label .= ' · bis ' . $loan->getDueAt()->format('d.m. H:i');
            }

            $bars[] = [
                'loan' => $loan,
                'col' => $startIndex,
                'span' => $endIndex - $startIndex + 1,
                'row' => $row,
                'label' => $label,
                'state' => $state,
                'clippedStart' => $start < $from,
                'clippedEnd' => $end > $to,
            ];
        }

        return [$bars, \count($rowEnds)];
    }
}
