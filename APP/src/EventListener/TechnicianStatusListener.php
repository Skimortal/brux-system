<?php

namespace App\EventListener;

use App\Entity\AppointmentTechnician;
use App\Service\TechnicianNotifier;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Sends the mails of the assignment workflow whenever an assignment gets a new status,
 * from the calendar forms as well as from the technicians' own answers.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class TechnicianStatusListener
{
    /** @var list<AppointmentTechnician> */
    private array $changed = [];

    public function __construct(private readonly TechnicianNotifier $notifier)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        // a series rewrites its members' assignments by deleting and re-adding them; an
        // assignment that comes back with the same status is no news
        $removed = [];
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AppointmentTechnician) {
                $removed[self::key($entity)] = $entity->getStatus();
            }
        }
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof AppointmentTechnician && !$entity->getAppointment()?->isQuiet() && ($removed[self::key($entity)] ?? null) !== $entity->getStatus()) {
                $this->changed[] = $entity;
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof AppointmentTechnician && isset($uow->getEntityChangeSet($entity)['status'])) {
                $this->changed[] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        // taken first: a mail failing or flushing again must not resend these
        $changed = $this->changed;
        $this->changed = [];

        // one mail per person and status, about the earliest appointment, e.g. for a new series
        $groups = [];
        foreach ($changed as $assignment) {
            $groups[$assignment->getContact()?->getId() . '|' . $assignment->getStatus()->value][] = $assignment;
        }
        foreach ($groups as $assignments) {
            usort($assignments, static fn (AppointmentTechnician $a, AppointmentTechnician $b) => $a->getAppointment()?->getStartDate() <=> $b->getAppointment()?->getStartDate());
            $this->notifier->statusChanged($assignments);
        }
    }

    private static function key(AppointmentTechnician $assignment): string
    {
        return spl_object_id($assignment->getAppointment()) . '|' . $assignment->getContact()?->getId();
    }
}
