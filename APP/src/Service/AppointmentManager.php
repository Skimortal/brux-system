<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\ProductionEvent;
use App\Enum\AppointmentStatusEnum;
use App\Enum\AppointmentTypeEnum;
use App\Enum\EventTypeEnum;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Creates, updates and deletes appointments including recurring series.
 */
final class AppointmentManager
{
    /** Upper bound of generated occurrences per series. */
    private const MAX_OCCURRENCES = 400;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AppointmentRepository $appointments,
    ) {
    }

    /**
     * Normalizes fields that depend on the type and the all-day flag.
     */
    public function normalize(Appointment $appointment): void
    {
        $type = $appointment->getType();
        if ($type !== AppointmentTypeEnum::PRODUCTION) {
            $appointment->setProduction(null)->setEventType(null)->setStatus(null)->setInternalTechniciansAttending(false);
        }
        if ($type !== AppointmentTypeEnum::CLEANING) {
            $appointment->setCleaningContact(null)->setCleaningAreas([]);
        } else {
            $appointment->setRoom(null);
        }
        if (!$type->hasAssignments()) {
            foreach ($appointment->getAppointmentTechnicians()->toArray() as $technician) {
                $appointment->removeAppointmentTechnician($technician);
            }
            foreach ($appointment->getAppointmentVolunteers()->toArray() as $volunteer) {
                $appointment->removeAppointmentVolunteer($volunteer);
            }
        }
        if ($appointment->isAllDay() && $appointment->getStartDate() !== null) {
            $start = \DateTime::createFromInterface($appointment->getStartDate())->setTime(0, 0);
            $end = \DateTime::createFromInterface($appointment->getEndDate() ?? $start)->setTime(23, 59, 59);
            $appointment->setStartDate($start)->setEndDate($end < $start ? (clone $start)->setTime(23, 59, 59) : $end);
        }
        $appointment->syncDerivedTitle();
    }

    public function create(Appointment $appointment, ?string $frequency, ?\DateTimeInterface $until): void
    {
        $this->normalize($appointment);
        $this->em->persist($appointment);
        if ($frequency !== null && $until !== null) {
            $this->generateSeries($appointment, $frequency, $until);
        }
        $this->em->flush();
    }

    /**
     * @param string $scope "single" or "series"
     */
    public function update(Appointment $appointment, string $scope, ?string $frequency, ?\DateTimeInterface $until): void
    {
        $this->normalize($appointment);

        if (!$appointment->isInSeries() && $frequency !== null && $until !== null) {
            $this->generateSeries($appointment, $frequency, $until);
        } elseif ($scope === 'series' && $appointment->isInSeries()) {
            $this->applyToSeries($appointment);
        }
        $this->em->flush();
    }

    public function duplicate(Appointment $appointment): Appointment
    {
        $copy = clone $appointment;
        if (!$copy->getType()->hasDerivedTitle()) {
            $title = (string) $copy->getTitle();
            $copy->setTitle(str_ends_with($title, '(Kopie)') ? $title : $title . ' (Kopie)');
        }
        $this->em->persist($copy);
        $this->em->flush();

        return $copy;
    }

    /**
     * @param string $scope "single" or "series"
     */
    public function delete(Appointment $appointment, string $scope): void
    {
        if ($scope === 'series' && $appointment->isInSeries()) {
            foreach ($this->appointments->findSeries($appointment) as $member) {
                $this->em->remove($member);
            }
        } else {
            if ($appointment->getParentAppointment() === null && $appointment->getRecurrenceFrequency() !== null) {
                $this->promoteNextSeriesRoot($appointment);
            }
            $this->em->remove($appointment);
        }
        $this->em->flush();
    }

    /**
     * The staffing appointment of a performance; a new, unsaved one if none exists.
     */
    public function staffingFor(ProductionEvent $performance): Appointment
    {
        $appointment = $performance->getAppointment();
        if ($appointment !== null) {
            return $appointment;
        }
        $appointment = (new Appointment())
            ->setType(AppointmentTypeEnum::PRODUCTION)
            ->setEventType(EventTypeEnum::EVENT)
            ->setStatus(AppointmentStatusEnum::CONFIRMED)
            ->setProduction($performance->getProduction())
            ->setRoom($performance->getRoom());
        $this->alignWithPerformance($appointment, $performance);

        return $appointment;
    }

    public function saveStaffing(ProductionEvent $performance, Appointment $appointment): void
    {
        $appointment->setType(AppointmentTypeEnum::PRODUCTION)
            ->setEventType(EventTypeEnum::EVENT)
            ->setProduction($performance->getProduction())
            ->setRoom($performance->getRoom())
            ->setProductionEvent($performance);
        $this->alignWithPerformance($appointment, $performance);
        $appointment->syncDerivedTitle();
        $this->em->persist($appointment);
        $this->em->flush();
    }

    /** Keeps the staffing appointment on the date and time of its performance. */
    public function alignWithPerformance(Appointment $appointment, ProductionEvent $performance): void
    {
        $start = $performance->getStartsAt();
        $end = $performance->getEndsAt();
        if ($start === null || $end === null) {
            return;
        }
        $appointment->setStartDate(\DateTime::createFromImmutable($start))
            ->setEndDate(\DateTime::createFromImmutable($end))
            ->setAllDay($performance->isAllDay())
            ->setRoom($performance->getRoom());
    }

    private function generateSeries(Appointment $root, string $frequency, \DateTimeInterface $until): void
    {
        $interval = match ($frequency) {
            'daily' => new \DateInterval('P1D'),
            'weekly' => new \DateInterval('P7D'),
            'monthly' => new \DateInterval('P1M'),
            default => null,
        };
        if ($interval === null) {
            return;
        }
        $root->setRecurrenceFrequency($frequency)->setRecurrenceEndDate(\DateTime::createFromInterface($until));
        $limit = \DateTimeImmutable::createFromInterface($until)->setTime(23, 59, 59);
        $start = \DateTimeImmutable::createFromInterface($root->getStartDate());
        $duration = $start->diff(\DateTimeImmutable::createFromInterface($root->getEndDate()));

        for ($i = 1; $i <= self::MAX_OCCURRENCES; ++$i) {
            $nextStart = $start;
            for ($k = 0; $k < $i; ++$k) {
                $nextStart = $nextStart->add($interval);
            }
            if ($nextStart > $limit) {
                break;
            }
            $occurrence = clone $root;
            $occurrence->setParentAppointment($root)
                ->setStartDate(\DateTime::createFromImmutable($nextStart))
                ->setEndDate(\DateTime::createFromImmutable($nextStart->add($duration)));
            $this->em->persist($occurrence);
        }
    }

    /**
     * Copies the fields of the edited appointment to every other member of its series,
     * keeping each member's date but applying the new time of day and duration.
     */
    private function applyToSeries(Appointment $source): void
    {
        $sourceStart = \DateTimeImmutable::createFromInterface($source->getStartDate());
        $duration = $sourceStart->diff(\DateTimeImmutable::createFromInterface($source->getEndDate()));

        foreach ($this->appointments->findSeries($source) as $member) {
            if ($member === $source) {
                continue;
            }
            $memberStart = \DateTimeImmutable::createFromInterface($member->getStartDate())
                ->setTime((int) $sourceStart->format('H'), (int) $sourceStart->format('i'));
            $member->setType($source->getType())
                ->setTitle($source->getTitle())
                ->setDescription($source->getDescription())
                ->setRoom($source->getRoom())
                ->setProduction($source->getProduction())
                ->setEventType($source->getEventType())
                ->setStatus($source->getStatus())
                ->setInternalTechniciansAttending($source->isInternalTechniciansAttending())
                ->setCleaningContact($source->getCleaningContact())
                ->setCleaningAreas($source->getCleaningAreas())
                ->setAllDay($source->isAllDay())
                ->setStartDate(\DateTime::createFromImmutable($memberStart))
                ->setEndDate(\DateTime::createFromImmutable($memberStart->add($duration)));

            foreach ($member->getAppointmentTechnicians()->toArray() as $technician) {
                $member->removeAppointmentTechnician($technician);
            }
            foreach ($source->getAppointmentTechnicians() as $technician) {
                $member->addAppointmentTechnician(clone $technician);
            }
            foreach ($member->getAppointmentVolunteers()->toArray() as $volunteer) {
                $member->removeAppointmentVolunteer($volunteer);
            }
            foreach ($source->getAppointmentVolunteers() as $volunteer) {
                $member->addAppointmentVolunteer(clone $volunteer);
            }
        }
    }

    /** When the first appointment of a series is deleted alone, the next one becomes the root. */
    private function promoteNextSeriesRoot(Appointment $root): void
    {
        $members = array_values(array_filter(
            $this->appointments->findSeries($root),
            static fn (Appointment $a) => $a !== $root
        ));
        if ($members === []) {
            return;
        }
        $newRoot = $members[0];
        $newRoot->setParentAppointment(null)
            ->setRecurrenceFrequency($root->getRecurrenceFrequency())
            ->setRecurrenceEndDate($root->getRecurrenceEndDate());
        foreach (\array_slice($members, 1) as $member) {
            $member->setParentAppointment($newRoot);
        }
    }
}
