<?php

namespace App\MessageHandler;

use App\Message\SendDigest;
use App\Repository\ContactRepository;
use App\Repository\DigestItemRepository;
use App\Service\TechnicianNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendDigestHandler
{
    public function __construct(
        private readonly DigestItemRepository $items,
        private readonly ContactRepository $contacts,
        private readonly TechnicianNotifier $notifier,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(SendDigest $message): void
    {
        // the worker runs for long: read what the office changed meanwhile
        $this->em->clear();

        $items = $this->items->findForContact($message->contactId);
        $contact = $this->contacts->find($message->contactId);
        if ($items === [] || $contact === null) {
            return;
        }
        // taken first: a failing mail must not send the same lines again on retry
        $this->em->getConnection()->executeStatement(
            'DELETE FROM digest_item WHERE id IN (?)',
            [array_map(static fn ($item) => $item->getId(), $items)],
            [\Doctrine\DBAL\ArrayParameterType::INTEGER],
        );
        $this->notifier->digest($contact, $items);
    }
}
