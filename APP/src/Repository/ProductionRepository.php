<?php

namespace App\Repository;

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
     * Productions with their next performance and number of performances.
     *
     * @return array<int, array{production: Production, next: ?\DateTimeInterface, upcoming: int, total: int}>
     */
    public function findOverview(bool $includePast, string $term = ''): array
    {
        $today = new \DateTimeImmutable('today');
        $qb = $this->createQueryBuilder('p')
            ->select('p AS production')
            ->addSelect('MIN(CASE WHEN pe.date >= :today THEN pe.date ELSE :far END) AS next')
            ->addSelect('SUM(CASE WHEN pe.date >= :today THEN 1 ELSE 0 END) AS upcoming')
            ->addSelect('COUNT(pe.id) AS total')
            ->leftJoin('p.events', 'pe')
            ->groupBy('p.id')
            ->setParameter('today', $today)
            ->setParameter('far', '9999-12-31');
        if (!$includePast) {
            $qb->having('upcoming > 0');
        }
        if ($term !== '') {
            $qb->andWhere('p.title LIKE :t')->setParameter('t', '%' . $term . '%');
        }
        $rows = $qb->getQuery()->getResult();
        foreach ($rows as &$row) {
            $row['next'] = $row['next'] !== null && $row['next'] !== '9999-12-31' ? new \DateTimeImmutable($row['next']) : null;
            $row['upcoming'] = (int) $row['upcoming'];
            $row['total'] = (int) $row['total'];
        }
        unset($row);
        usort($rows, static fn ($a, $b) => [$a['next'] === null, $a['next'], $a['production']->getTitle()] <=> [$b['next'] === null, $b['next'], $b['production']->getTitle()]);

        return $rows;
    }
}
