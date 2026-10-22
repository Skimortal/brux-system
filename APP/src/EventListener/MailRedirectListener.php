<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * On test systems every mail goes to one address (MAILER_REDIRECT_TO) instead of the real
 * recipients, whose addresses stay visible in the To header. Unset in production.
 */
#[AsEventListener(event: MessageEvent::class)]
final class MailRedirectListener
{
    public function __construct(#[Autowire('%env(default::MAILER_REDIRECT_TO)%')] private readonly ?string $redirectTo)
    {
    }

    public function __invoke(MessageEvent $event): void
    {
        // only when the mail actually leaves, not when it is put in the queue
        if ($event->isQueued() || trim((string) $this->redirectTo) === '') {
            return;
        }
        $event->getEnvelope()->setRecipients([new Address(trim((string) $this->redirectTo))]);
        $message = $event->getMessage();
        if ($message instanceof Email && !str_starts_with((string) $message->getSubject(), '[Test] ')) {
            $message->subject('[Test] ' . $message->getSubject());
        }
    }
}
