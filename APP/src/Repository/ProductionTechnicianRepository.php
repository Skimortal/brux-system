<?php

namespace App\Repository;

use App\Entity\ProductionTechnician;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductionTechnician>
 */
class ProductionTechnicianRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductionTechnician::class);
    }
}
