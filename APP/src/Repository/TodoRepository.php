<?php

namespace App\Repository;

use App\Entity\Todo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Todo>
 */
class TodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }

    /**
     * General todos (without a group) ticked off most recently, newest first.
     *
     * @return list<Todo>
     */
    public function findRecentlyDone(int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.done = true')
            ->andWhere('t.group IS NULL')
            ->orderBy('t.doneAt', 'DESC')
            ->addOrderBy('t.updatedAt', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
