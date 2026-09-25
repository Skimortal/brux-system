<?php

namespace App\Service;

use App\Entity\CleaningException;
use App\Entity\CleaningSchedule;
use App\Entity\Contact;
use App\Repository\AppointmentRepository;
use App\Repository\CleaningScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Cancelling single occurrences of a cleaning schedule and the monthly report.
 */
final class CleaningService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CleaningScheduleRepository $schedules,
        private readonly AppointmentRepository $appointments,
    ) {
    }

    public function setCancelled(CleaningSchedule $schedule, \DateTimeImmutable $day, bool $cancelled): void
    {
        $existing = $schedule->getCancellation($day);
        if ($cancelled && $existing === null && $schedule->occursOn($day)) {
            $exception = new CleaningException($schedule, $day);
            $schedule->getExceptions()->add($exception);
            $this->em->persist($exception);
        } elseif (!$cancelled && $existing !== null) {
            $schedule->getExceptions()->removeElement($existing);
            $this->em->remove($existing);
        }
        $this->em->flush();
    }

    /**
     * @return array{
     *     regular: array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable, cancelled: bool}>,
     *     extra: \App\Entity\Appointment[],
     *     hours: float
     * }
     */
    public function monthlyReport(Contact $contact, \DateTimeImmutable $month): array
    {
        $from = $month->modify('first day of this month')->setTime(0, 0);
        $to = $from->modify('first day of next month');

        $regular = [];
        foreach ($this->schedules->findActiveBetween($from, $to) as $schedule) {
            if ($schedule->getCleaningContact()?->getId() !== $contact->getId()) {
                continue;
            }
            for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
                if ($schedule->occursOn($day)) {
                    $regular[] = [
                        'start' => $schedule->startOn($day),
                        'end' => $schedule->endOn($day),
                        'cancelled' => $schedule->isCancelledOn($day),
                    ];
                }
            }
        }
        usort($regular, static fn ($a, $b) => $a['start'] <=> $b['start']);

        $extra = $this->appointments->findCleaningsOfContact((int) $contact->getId(), $from, $to);

        $seconds = 0;
        foreach ($regular as $occurrence) {
            if (!$occurrence['cancelled']) {
                $seconds += $occurrence['end']->getTimestamp() - $occurrence['start']->getTimestamp();
            }
        }
        foreach ($extra as $appointment) {
            $seconds += $appointment->getEndDate()->getTimestamp() - $appointment->getStartDate()->getTimestamp();
        }

        return ['regular' => $regular, 'extra' => $extra, 'hours' => round($seconds / 3600, 2)];
    }
}
