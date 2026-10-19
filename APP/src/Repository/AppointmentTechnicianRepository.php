<?php

namespace App\Repository;

use App\Entity\AppointmentTechnician;
use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppointmentTechnician>
 */
class AppointmentTechnicianRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppointmentTechnician::class);
    }

    /**
     * A technician's assignments overlapping [$from, $to), by date.
     *
     * @return list<AppointmentTechnician>
     */
    public function findForContact(Contact $contact, \DateTimeInterface $from, ?\DateTimeInterface $to = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->addSelect('a', 'r', 'pe', 'p')
            ->innerJoin('t.appointment', 'a')
            ->leftJoin('a.room', 'r')
            ->leftJoin('a.productionEvent', 'pe')
            ->leftJoin('a.production', 'p')
            ->andWhere('t.contact = :contact')
            ->andWhere('a.endDate > :from')
            ->setParameter('contact', $contact)
            ->setParameter('from', $from)
            ->orderBy('a.startDate', 'ASC');
        if ($to !== null) {
            $qb->andWhere('a.startDate < :to')->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }
}
