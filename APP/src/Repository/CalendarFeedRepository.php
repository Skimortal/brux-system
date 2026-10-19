<?php

namespace App\Repository;

use App\Entity\CalendarFeed;
use App\Entity\Contact;
use App\Enum\FeedProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CalendarFeed>
 */
class CalendarFeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarFeed::class);
    }

    public function findOneForContact(Contact $contact, FeedProfile $profile): ?CalendarFeed
    {
        return $this->findOneBy(['contact' => $contact, 'profile' => $profile]);
    }
}
