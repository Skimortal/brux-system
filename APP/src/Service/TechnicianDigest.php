<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Contact;
use App\Enum\DigestKind;
use App\Message\SendDigest;
use Doctrine\DBAL\Connection;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

/**
 * Holds back the mails to technicians and volunteers so each person gets one mail a day.
 * News about an appointment in the next two days still goes out right away.
 */
class TechnicianDigest
{
    public const TIMEZONE = 'Europe/Vienna';
    // appointments starting sooner than this are mailed right away
    public const SHORT_NOTICE = '+48 hours';
    // a pending digest older than this lost its message (e.g. the worker was down); send again
    private const STALE = '-25 hours';

    public function __construct(
        private readonly Connection $db,
        private readonly MessageBusInterface $bus,
        private readonly AppSettings $settings,
        private readonly ClockInterface $clock,
    ) {
    }

    /** Whether news about this appointment waits for the digest instead of a mail of its own. */
    public function defers(Appointment $appointment): bool
    {
        $start = $appointment->getStartDate();

        return $this->settings->digestEnabled()
            && $start !== null
            && \DateTimeImmutable::createFromInterface($start) > $this->clock->now()->modify(self::SHORT_NOTICE);
    }

    /**
     * Adds a line to the person's next digest. Plain SQL: this runs while Doctrine flushes.
     */
    public function queue(DigestKind $kind, Contact $contact, Appointment $appointment, int $more = 0): void
    {
        if ($contact->getId() === null || $appointment->getId() === null) {
            return;
        }
        $now = $this->clock->now();
        $oldest = $this->db->fetchOne('SELECT MIN(created_at) FROM digest_item WHERE contact_id = ?', [$contact->getId()]);

        // the same news twice (e.g. sent out again) is one line
        $this->db->executeStatement(
            'DELETE FROM digest_item WHERE contact_id = ? AND appointment_id = ? AND kind = ?',
            [$contact->getId(), $appointment->getId(), $kind->value],
        );
        $this->db->insert('digest_item', [
            'contact_id' => $contact->getId(),
            'appointment_id' => $appointment->getId(),
            'kind' => $kind->value,
            'more' => $more,
            'created_at' => $now->format('Y-m-d H:i:s'),
        ]);

        // the first line schedules the mail; later ones ride along
        if ($oldest === false || $oldest === null || new \DateTimeImmutable($oldest) < $now->modify(self::STALE)) {
            $delay = max(0, $this->nextRun()->getTimestamp() - $now->getTimestamp());
            $this->bus->dispatch(new SendDigest($contact->getId()), [new DelayStamp($delay * 1000)]);
        }
    }

    /** The next time the digest mails go out. */
    public function nextRun(): \DateTimeImmutable
    {
        $now = $this->clock->now()->setTimezone(new \DateTimeZone(self::TIMEZONE));
        $run = $now->setTime($this->settings->digestHour(), 0);

        return $run > $now ? $run : $run->modify('+1 day');
    }

    /** E.g. "heute um 17:00 Uhr". */
    public function nextRunLabel(): string
    {
        $run = $this->nextRun();
        $today = $this->clock->now()->setTimezone(new \DateTimeZone(self::TIMEZONE))->format('Y-m-d');

        return ($run->format('Y-m-d') === $today ? 'heute' : 'morgen') . ' um ' . $run->format('H:i') . ' Uhr';
    }
}
