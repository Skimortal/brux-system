<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * Appointments overlapping [$from, $to). Staffing appointments of performances are
     * included; the calendar merges them into their performance.
     *
     * @return Appointment[]
     */
    public function findOverlapping(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('r', 'p', 'cc', 'at', 'atc', 'av', 'avc')
            ->leftJoin('a.room', 'r')
            ->leftJoin('a.production', 'p')
            ->leftJoin('a.cleaningContact', 'cc')
            ->leftJoin('a.appointmentTechnicians', 'at')
            ->leftJoin('at.contact', 'atc')
            ->leftJoin('a.appointmentVolunteers', 'av')
            ->leftJoin('av.contact', 'avc')
            ->andWhere('a.startDate < :to')
            ->andWhere('a.endDate > :from')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('a.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Appointment[] all appointments of the series, ordered by start
     */
    public function findSeries(Appointment $appointment): array
    {
        $root = $appointment->getSeriesRoot();

        return $this->createQueryBuilder('a')
            ->andWhere('a = :root OR a.parentAppointment = :root')
            ->setParameter('root', $root)
            ->orderBy('a.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Appointment[] extra cleaning appointments of a contact within a month
     */
    public function findCleaningsOfContact(int $contactId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.type = :type')
            ->andWhere('IDENTITY(a.cleaningContact) = :contact')
            ->andWhere('a.startDate >= :from AND a.startDate < :to')
            ->setParameter('type', 'cleaning')
            ->setParameter('contact', $contactId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('a.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
