<?php

namespace App\Repository;

use App\Entity\CleaningSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CleaningSchedule>
 */
class CleaningScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CleaningSchedule::class);
    }

    /** @return CleaningSchedule[] schedules active at some point within the range */
    public function findActiveBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('s')
            ->addSelect('c', 'e')
            ->innerJoin('s.cleaningContact', 'c')
            ->leftJoin('s.exceptions', 'e')
            ->andWhere('s.activeFrom IS NULL OR s.activeFrom < :to')
            ->andWhere('s.activeTo IS NULL OR s.activeTo >= :from')
            ->setParameter('from', \DateTimeImmutable::createFromInterface($from)->setTime(0, 0))
            ->setParameter('to', \DateTimeImmutable::createFromInterface($to))
            ->getQuery()
            ->getResult();
    }
}
