<?php

namespace App\Repository;

use App\Entity\KeyManagement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KeyManagement>
 */
class KeyManagementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeyManagement::class);
    }

    /** @return KeyManagement[] keys with rooms and loans, ordered by name */
    public function findAllWithLoans(): array
    {
        return $this->createQueryBuilder('k')
            ->addSelect('r', 'l', 'lc', 'lu')
            ->leftJoin('k.rooms', 'r')
            ->leftJoin('k.loans', 'l')
            ->leftJoin('l.contact', 'lc')
            ->leftJoin('l.user', 'lu')
            ->orderBy('k.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
