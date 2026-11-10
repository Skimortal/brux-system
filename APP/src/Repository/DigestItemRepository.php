<?php

namespace App\Repository;

use App\Entity\DigestItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DigestItem>
 */
class DigestItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DigestItem::class);
    }

    /** @return list<DigestItem> one person's news, earliest appointment first */
    public function findForContact(int $contactId): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.appointment', 'a')->addSelect('a')
            ->andWhere('IDENTITY(d.contact) = :contact')
            ->setParameter('contact', $contactId)
            ->orderBy('a.startDate', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
