<?php

namespace App\Repository;

use App\Entity\KeyLoan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KeyLoan>
 */
class KeyLoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KeyLoan::class);
    }

    /**
     * Loans that overlap [$from, $to). Open loans without a planned return count as ongoing.
     *
     * @return KeyLoan[]
     */
    public function findOverlapping(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('l')
            ->addSelect('k', 'c', 'u')
            ->innerJoin('l.key', 'k')
            ->leftJoin('l.contact', 'c')
            ->leftJoin('l.user', 'u')
            ->andWhere('l.startAt < :to')
            ->andWhere('COALESCE(l.returnedAt, l.dueAt) IS NULL OR COALESCE(l.returnedAt, l.dueAt) > :from OR (l.returnedAt IS NULL AND l.dueAt < :from)')
            ->setParameter('from', \DateTimeImmutable::createFromInterface($from))
            ->setParameter('to', \DateTimeImmutable::createFromInterface($to))
            ->orderBy('l.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
