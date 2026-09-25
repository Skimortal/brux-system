<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Service\BruxApiSyncService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SyncController extends AbstractController
{
    use CalendarActionTrait;

    #[Route('/wordpress/sync', name: 'app_wordpress_sync', methods: ['POST'])]
    public function sync(Request $request, BruxApiSyncService $sync, CalendarPage $page, CsrfTokenManagerInterface $csrf): Response
    {
        $this->requireCsrf($request, $csrf, 'wordpress-sync');
        $stats = $sync->syncFromApi();

        if ($stats['errors'] !== []) {
            $this->addFlash('danger', 'WordPress-Import mit Fehlern: ' . implode(' · ', \array_slice($stats['errors'], 0, 3)));
        } else {
            $this->addFlash('success', sprintf(
                'WordPress-Import fertig: %d neue und %d aktualisierte Vorstellungen.',
                $stats['events_created'],
                $stats['events_updated']
            ));
        }

        return $this->backToCalendar($request, $page);
    }
}
