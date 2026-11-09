<?php

namespace App\EventListener;

use App\Entity\AppointmentVolunteer;
use App\Service\TechnicianNotifier;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Lets a volunteer know by mail when they are assigned to an appointment.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class VolunteerAssignmentListener
{
    /** @var list<AppointmentVolunteer> */
    private array $added = [];

    public function __construct(private readonly TechnicianNotifier $notifier)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        // a series rewrites its members' assignments by deleting and re-adding them; a
        // volunteer who was there before is no news
        $removed = [];
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AppointmentVolunteer) {
                $removed[self::key($entity)] = true;
            }
        }
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof AppointmentVolunteer && !isset($removed[self::key($entity)])) {
                $this->added[] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        // taken first: a mail failing or flushing again must not resend these
        $added = $this->added;
        $this->added = [];

        // one mail per person, about the earliest appointment, e.g. for a new series
        $groups = [];
        foreach ($added as $assignment) {
            $groups[(string) $assignment->getContact()?->getId()][] = $assignment;
        }
        foreach ($groups as $assignments) {
            usort($assignments, static fn (AppointmentVolunteer $a, AppointmentVolunteer $b) => $a->getAppointment()?->getStartDate() <=> $b->getAppointment()?->getStartDate());
            $this->notifier->volunteerAssigned($assignments);
        }
    }

    private static function key(AppointmentVolunteer $assignment): string
    {
        return spl_object_id($assignment->getAppointment()) . '|' . $assignment->getContact()?->getId();
    }
}
