<?php

namespace App\Repository;

use App\DTO\ProductionFilter;
use App\Entity\Production;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Production>
 */
class ProductionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Production::class);
    }

    public function findByExternalId(int $externalId): ?Production
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * @return Production[]
     */
    public function findByFilter(ProductionFilter $filter): array
    {
        if ($filter->isShowAll()) {
            return $this->createQueryBuilder('p')
                ->orderBy('p.title', 'ASC')
                ->getQuery()
                ->getResult();
        }

        // "aktiv" = hat mindestens ein Event mit date >= heute
        $today = (new \DateTimeImmutable('today'))->setTime(0, 0, 0);

        return $this->createQueryBuilder('p')
            ->innerJoin('p.events', 'pe')
            ->andWhere('pe.date IS NOT NULL')
            ->andWhere('pe.date >= :today')
            ->setParameter('today', $today)
            ->distinct()
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
