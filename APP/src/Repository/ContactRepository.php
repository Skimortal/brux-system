<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Enum\ContactRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * @param int[] $categoryIds empty = all
     *
     * @return Contact[]
     */
    public function search(?string $term, array $categoryIds = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->addSelect('cat')
            ->innerJoin('c.category', 'cat')
            ->orderBy('cat.name', 'ASC')
            ->addOrderBy('c.name', 'ASC');

        if ($categoryIds !== []) {
            $qb->andWhere('cat.id IN (:cats)')->setParameter('cats', $categoryIds);
        }
        if ($term !== null && trim($term) !== '') {
            $qb->andWhere('c.name LIKE :t OR c.company LIKE :t OR c.email LIKE :t OR c.phone LIKE :t')
                ->setParameter('t', '%' . trim($term) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function queryByRole(ContactRole $role): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.category', 'cat')
            ->andWhere('cat.role = :role')
            ->setParameter('role', $role)
            ->orderBy('c.name', 'ASC');
    }

    /** @return Contact[] */
    public function findByRole(ContactRole $role): array
    {
        return $this->queryByRole($role)->getQuery()->getResult();
    }
}
