<?php

namespace App\Repository;

use App\Entity\Production;
use App\Entity\ProductionEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductionEvent>
 */
class ProductionEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductionEvent::class);
    }

    /**
     * Performances whose date lies within [$from, $to).
     *
     * @return ProductionEvent[]
     */
    public function findBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('pe')
            ->addSelect('p', 'r', 'a', 'cat')
            ->innerJoin('pe.production', 'p')
            ->leftJoin('pe.categories', 'cat')
            ->leftJoin('pe.room', 'r')
            ->leftJoin('pe.appointment', 'a')
            ->andWhere('pe.date IS NOT NULL')
            ->andWhere('pe.date >= :from AND pe.date < :to')
            ->setParameter('from', \DateTimeImmutable::createFromInterface($from)->setTime(0, 0))
            ->setParameter('to', \DateTimeImmutable::createFromInterface($to))
            ->orderBy('pe.date', 'ASC')
            ->addOrderBy('pe.timeFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** The event index for a new performance, following the ones WordPress numbered. */
    public function nextEventIndex(Production $production): int
    {
        $max = $this->createQueryBuilder('pe')
            ->select('MAX(pe.eventIndex)')
            ->andWhere('pe.production = :production')
            ->setParameter('production', $production)
            ->getQuery()
            ->getSingleScalarResult();

        return $max === null ? 0 : (int) $max + 1;
    }

    public function findLastSyncedAt(): ?\DateTimeImmutable
    {
        $value = $this->createQueryBuilder('pe')
            ->select('MAX(pe.syncedAt)')
            ->getQuery()
            ->getSingleScalarResult();

        return $value ? new \DateTimeImmutable($value) : null;
    }
}
