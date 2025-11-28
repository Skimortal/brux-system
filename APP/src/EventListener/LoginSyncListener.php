<?php

namespace App\EventListener;

use App\Service\BruxApiSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSyncListener
{
    private const SYNC_INTERVAL_HOURS = 24;

    public function __construct(
        private BruxApiSyncService $syncService,
        private LoggerInterface $logger
    ) {}

    public function __invoke(LoginSuccessEvent $event): void
    {
        // Check if sync is needed
        if (!$this->shouldSync()) {
            return;
        }

        // Run sync in background (non-blocking)
        try {
            $this->syncService->syncFromApi();
            $this->updateLastSyncTime();
        } catch (\Exception $e) {
            // Don't block login on sync errors
            $this->logger->error('Login sync failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldSync(): bool
    {
        $lastSync = $this->getLastSyncTime();

        if (!$lastSync) {
            return true;
        }

        $now = new \DateTime();
        $interval = $now->diff($lastSync);

        return $interval->h >= self::SYNC_INTERVAL_HOURS || $interval->days > 0;
    }

    private function getLastSyncTime(): ?\DateTime
    {
        $cacheDir = sys_get_temp_dir();
        $cacheFile = $cacheDir . '/brux_last_sync.txt';

        if (!file_exists($cacheFile)) {
            return null;
        }

        $timestamp = file_get_contents($cacheFile);
        if (!$timestamp) {
            return null;
        }

        try {
            return new \DateTime('@' . $timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function updateLastSyncTime(): void
    {
        $cacheDir = sys_get_temp_dir();
        $cacheFile = $cacheDir . '/brux_last_sync.txt';

        file_put_contents($cacheFile, time());
    }
}
