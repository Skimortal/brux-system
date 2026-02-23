<?php

namespace App\Repository;

use App\Entity\CleaningSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CleaningScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CleaningSchedule::class);
    }
}
