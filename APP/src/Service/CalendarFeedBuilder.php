<?php

namespace App\Service;

use App\Calendar\CalendarBuilder;
use App\Calendar\CalendarEntry;
use App\Calendar\EntryGroup;
use App\Entity\CalendarFeed;
use App\Entity\Production;
use App\Entity\Room;
use App\Enum\FeedProfile;
use App\Repository\AppointmentRepository;
use App\Repository\AppointmentTechnicianRepository;
use App\Repository\ProductionEventRepository;
use App\Repository\ProductionRepository;
use App\Repository\RoomRepository;
use Symfony\Component\Clock\ClockInterface;

/**
 * Turns the calendar into iCalendar files for subscriptions (Apple, Google, Outlook).
 * Times are written in UTC, so no time zone definitions are needed.
 */
class CalendarFeedBuilder
{
    /** How far back and ahead a subscription reaches. */
    private const PAST = '-60 days';
    private const AHEAD = '+400 days';

    public function __construct(
        private readonly CalendarBuilder $calendar,
        private readonly AppointmentTechnicianRepository $assignments,
        private readonly RoomRepository $rooms,
        private readonly ClockInterface $clock,
        private readonly ProductionRepository $productions,
        private readonly ProductionEventRepository $performances,
        private readonly AppointmentRepository $appointments,
        private readonly WebsiteContent $content,
    ) {
    }

    public function build(CalendarFeed $feed, string $part, ?Room $room): string
    {
        $now = $this->clock->now();
        $from = $now->modify(self::PAST)->setTime(0, 0);
        $to = $now->modify(self::AHEAD)->setTime(0, 0);
        $roomNames = [];
        foreach ($this->rooms->findAll() as $r) {
            $roomNames[$r->getId()] = (string) $r->getName();
        }

        $events = $feed->getProfile() === FeedProfile::MINE
            ? $this->ownAssignments($feed, $from, $to, $roomNames)
            : $this->fromCalendar($feed->getProfile(), $part, $room, $from, $to, $roomNames);

        $name = 'BRUX · ' . ($feed->getProfile()->parts()[$part]['label'] ?? $feed->getProfile()->label()) . ($room !== null ? ' · ' . $room->getName() : '');

        return $this->ics($name, $events, $now);
    }

    /**
     * @param array<int, string> $roomNames
     *
     * @return list<array<string, mixed>>
     */
    private function fromCalendar(FeedProfile $profile, string $part, ?Room $room, \DateTimeImmutable $from, \DateTimeImmutable $to, array $roomNames): array
    {
        $keep = match ([$profile, $part]) {
            [FeedProfile::BOARD, 'premieren'] => static fn (CalendarEntry $e) => $e->premiere,
            [FeedProfile::BOARD, 'veranstaltungen'] => static fn (CalendarEntry $e) => $e->boardVisible,
            [FeedProfile::MANAGEMENT, 'aufenthalte'] => static fn (CalendarEntry $e) => $e->group === EntryGroup::STAY,
            default => static fn (CalendarEntry $e) => $e->group !== EntryGroup::STAY,
        };
        $events = [];
        foreach ($this->calendar->collectEntries($from, $to) as $entry) {
            if (!$keep($entry) || ($room !== null && !$entry->belongsToRoom((int) $room->getId()))) {
                continue;
            }
            $prefix = match (true) {
                $entry->cancelled => 'Abgesagt: ',
                $entry->tentative => 'Vielleicht: ',
                // in the board's main calendar premieres stand out by name
                $entry->premiere && $profile === FeedProfile::BOARD && $part === 'veranstaltungen' => 'PREMIERE: ',
                default => '',
            };
            $production = $this->productionOf($entry->panel);
            $events[] = [
                'uid' => $entry->panel . '-' . $entry->start->format('Ymd'),
                'summary' => $prefix . $entry->title,
                'start' => $entry->start,
                'end' => $entry->end,
                'allDay' => $entry->allDay,
                'location' => $entry->roomId !== null ? ($roomNames[$entry->roomId] ?? null) : null,
                'description' => $this->describe($entry->meta, $production),
                'url' => $production?->getPermalink(),
                'status' => $entry->cancelled ? 'CANCELLED' : ($entry->tentative ? 'TENTATIVE' : 'CONFIRMED'),
            ];
        }

        return $events;
    }

    /**
     * @param array<int, string> $roomNames
     *
     * @return list<array<string, mixed>>
     */
    private function ownAssignments(CalendarFeed $feed, \DateTimeImmutable $from, \DateTimeImmutable $to, array $roomNames): array
    {
        $contact = $feed->getContact();
        if ($contact === null) {
            return [];
        }
        $events = [];
        foreach ($this->assignments->findForContact($contact, $from, $to) as $assignment) {
            if (!$assignment->getStatus()->isStaffed()) {
                continue;
            }
            $appointment = $assignment->getAppointment();
            $performance = $appointment->getProductionEvent();
            $title = $performance !== null ? (string) $performance->getProduction()?->getTitle() : $appointment->getDisplayTitle();
            $tasks = array_keys(array_filter(['Licht' => $assignment->isLighting(), 'Ton' => $assignment->isSound(), 'Aufbau' => $assignment->isSetup()]));
            $start = \DateTimeImmutable::createFromInterface($appointment->getStartDate());
            $end = \DateTimeImmutable::createFromInterface($appointment->getEndDate());
            $events[] = [
                'uid' => 'einsatz-' . $assignment->getId(),
                'summary' => '★ ' . $title . ($assignment->isConfirmed() ? '' : ' (' . $assignment->getStatus()->label() . ')'),
                'start' => $appointment->isAllDay() ? $start->setTime(0, 0) : $start,
                'end' => $appointment->isAllDay() ? $end->setTime(0, 0)->modify('+1 day') : $end,
                'allDay' => $appointment->isAllDay(),
                'location' => $appointment->getRoom() !== null ? ($roomNames[$appointment->getRoom()->getId()] ?? null) : null,
                'description' => $this->describe(trim('Status: ' . $assignment->getStatus()->label() . ($tasks !== [] ? "\nAufgabe: " . implode(', ', $tasks) : '')), $appointment->getProduction() ?? $performance?->getProduction()),
                'url' => ($appointment->getProduction() ?? $performance?->getProduction())?->getPermalink(),
                'status' => $assignment->isConfirmed() ? 'CONFIRMED' : 'TENTATIVE',
            ];
        }

        return $events;
    }

    /**
     * The entry's own details, then the infoscreen text and the website link of its
     * production. Most calendar apps only show the description, so the link goes there too.
     */
    private function describe(?string $details, ?Production $production): string
    {
        $parts = [trim((string) $details)];
        if ($production !== null) {
            $parts[] = $this->content->infoscreenText($production);
            $parts[] = (string) $production->getPermalink();
        }

        return implode("\n\n", array_filter($parts, static fn (string $p) => $p !== ''));
    }

    /** The production behind a calendar entry (performance, appointment or stay). */
    private function productionOf(string $panel): ?Production
    {
        [$kind, $id] = array_pad(explode('-', $panel, 2), 2, '');
        if (!ctype_digit($id)) {
            return null;
        }

        return match ($kind) {
            'p' => $this->productions->find((int) $id),
            'pe' => $this->performances->find((int) $id)?->getProduction(),
            'a' => $this->appointments->find((int) $id)?->getProduction(),
            default => null,
        };
    }

    /**
     * @param list<array<string, mixed>> $events
     */
    private function ics(string $name, array $events, \DateTimeImmutable $now): string
    {
        $utc = new \DateTimeZone('UTC');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//BRUX Freies Theater Innsbruck//Haussystem//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . self::escape($name),
            'X-WR-TIMEZONE:Europe/Vienna',
            // clients that honour it refresh every hour
            'REFRESH-INTERVAL;VALUE=DURATION:PT1H',
            'X-PUBLISHED-TTL:PT1H',
        ];
        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . sha1($event['uid']) . '@brux-haussystem';
            $lines[] = 'DTSTAMP:' . $now->setTimezone($utc)->format('Ymd\THis\Z');
            if ($event['allDay']) {
                $lines[] = 'DTSTART;VALUE=DATE:' . $event['start']->format('Ymd');
                $lines[] = 'DTEND;VALUE=DATE:' . $event['end']->format('Ymd');
            } else {
                $lines[] = 'DTSTART:' . $event['start']->setTimezone($utc)->format('Ymd\THis\Z');
                $lines[] = 'DTEND:' . $event['end']->setTimezone($utc)->format('Ymd\THis\Z');
            }
            $lines[] = 'SUMMARY:' . self::escape($event['summary']);
            if ($event['location']) {
                $lines[] = 'LOCATION:' . self::escape($event['location']);
            }
            if ($event['description']) {
                $lines[] = 'DESCRIPTION:' . self::escape($event['description']);
            }
            if ($event['url'] ?? null) {
                $lines[] = 'URL:' . $event['url'];
            }
            $lines[] = 'STATUS:' . $event['status'];
            $lines[] = 'TRANSP:OPAQUE';
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map(self::fold(...), $lines)) . "\r\n";
    }

    public static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** Lines longer than 75 octets continue on the next line after a space (RFC 5545). */
    public static function fold(string $line): string
    {
        if (\strlen($line) <= 75) {
            return $line;
        }
        $out = '';
        $current = '';
        foreach (mb_str_split($line) as $char) {
            if (\strlen($current) + \strlen($char) > ($out === '' ? 75 : 74)) {
                $out .= ($out === '' ? '' : "\r\n ") . $current;
                $current = '';
            }
            $current .= $char;
        }

        return $out . "\r\n " . $current;
    }
}
