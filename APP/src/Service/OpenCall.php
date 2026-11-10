<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Contact;
use App\Enum\ContactRole;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Sends an appointment out to all technicians as open, so they can show interest with one click.
 */
final class OpenCall
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TechnicianNotifier $notifier,
        private readonly ContactRepository $contacts,
        private readonly ClockInterface $clock,
        private readonly TechnicianDigest $digest,
    ) {
    }

    /**
     * @return int|null the number of mails sent, null when the appointment is already over
     */
    public function send(Appointment $appointment): ?int
    {
        if ($appointment->getEndDate() <= $this->clock->now()) {
            return null;
        }
        $appointment->setOpenCallAt($this->clock->now());
        $this->em->flush();

        // everyone in the technicians' category with an address who has no assignment here yet
        $recipients = array_filter(
            $this->contacts->queryByRole(ContactRole::TECHNICIAN)->getQuery()->getResult(),
            static fn (Contact $c) => trim((string) $c->getEmail()) !== '' && $appointment->getAssignmentOf($c) === null,
        );

        return $this->notifier->openCall($appointment, $recipients);
    }

    /**
     * The flash message for the result of send().
     *
     * @return array{0: string, 1: string} type and text
     */
    public function message(Appointment $appointment, ?int $sent): array
    {
        $people = sprintf('%d Techniker:in%s', (int) $sent, $sent === 1 ? '' : 'nen');

        return match (true) {
            $sent === null => ['danger', 'Der Termin ist schon vorbei.'],
            $sent > 0 && $this->digest->defers($appointment) => ['success', sprintf('Ausgeschrieben an %s, die Mail kommt mit der Sammelmail %s.', $people, $this->digest->nextRunLabel())],
            $sent > 0 => ['success', 'Ausgeschrieben an ' . $people . '.'],
            default => ['warning', 'Ausgeschrieben, aber niemand bekam eine Mail (keine Techniker:innen mit E-Mail-Adresse ohne Einteilung).'],
        };
    }
}
