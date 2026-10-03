<?php

namespace App\Repository;

use App\Entity\ProductionCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductionCategory>
 */
class ProductionCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductionCategory::class);
    }

    public function findByExternalId(int $externalId): ?ProductionCategory
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }
}
