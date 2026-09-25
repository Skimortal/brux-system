<?php

namespace App\EventListener;

use App\Service\BruxApiSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * After a login, imports from WordPress when the last sync is older than a day.
 * The import runs after the response has been sent, so the login is not delayed.
 */
final class BackgroundSyncListener
{
    private const MAX_AGE = 'PT24H';

    private bool $pending = false;

    public function __construct(
        private readonly BruxApiSyncService $syncService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onLogin(LoginSuccessEvent $event): void
    {
        $last = $this->syncService->getLastSyncTime();
        $this->pending = $last === null || $last < (new \DateTimeImmutable())->sub(new \DateInterval(self::MAX_AGE));
    }

    #[AsEventListener(event: KernelEvents::TERMINATE)]
    public function onTerminate(TerminateEvent $event): void
    {
        if (!$this->pending) {
            return;
        }
        $this->pending = false;
        try {
            $this->syncService->syncFromApi();
        } catch (\Throwable $e) {
            $this->logger->error('Background sync after login failed', ['error' => $e->getMessage()]);
        }
    }
}
