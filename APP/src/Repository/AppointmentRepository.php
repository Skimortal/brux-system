<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findAllForCalendar(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDate(\DateTimeInterface $date): array
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('a')
            ->where('a.startDate BETWEEN :start AND :end')
            ->orWhere('a.endDate BETWEEN :start AND :end')
            ->orWhere('a.startDate <= :start AND a.endDate >= :end')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->orderBy('a.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
