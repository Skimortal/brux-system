<?php

namespace App\Repository;

use App\Entity\TodoGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TodoGroup>
 */
class TodoGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TodoGroup::class);
    }

    /**
     * @return list<TodoGroup>
     */
    public function findAllWithTodos(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.todos', 't')->addSelect('t')
            ->orderBy('g.name', 'ASC')->addOrderBy('t.id', 'ASC')
            ->getQuery()->getResult();
    }
}
