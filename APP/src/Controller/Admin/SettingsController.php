<?php

namespace App\Controller\Admin;

use App\Repository\ContactCategoryRepository;
use App\Service\BruxApiSyncService;
use App\Service\FieldOwnership;
use App\Service\WordPressPushService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    #[Route('/verwaltung', name: 'app_settings', methods: ['GET'])]
    public function index(BruxApiSyncService $sync, WordPressPushService $push, FieldOwnership $ownership, ContactCategoryRepository $categories): Response
    {
        $missingRoles = [];
        foreach (\App\Enum\ContactRole::cases() as $role) {
            if ($categories->findOneBy(['role' => $role]) === null) {
                $missingRoles[] = $role->label();
            }
        }

        return $this->render('settings/index.html.twig', [
            'last_sync' => $sync->getLastSyncTime(),
            'sync_url' => $sync->getApiUrl(),
            'push_configured' => $push->isConfigured(),
            'wordpress_leads' => !$ownership->canCreatePerformances(),
            'missing_roles' => $missingRoles,
        ]);
    }
}
