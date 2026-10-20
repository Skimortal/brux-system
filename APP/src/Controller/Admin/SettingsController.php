<?php

namespace App\Controller\Admin;

use App\Repository\ContactCategoryRepository;
use App\Service\AppSettings;
use App\Service\BruxApiSyncService;
use App\Service\FieldOwnership;
use App\Service\WordPressPushService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Attribute\Route;

final class SettingsController extends AbstractController
{
    #[Route('/verwaltung', name: 'app_settings', methods: ['GET'])]
    public function index(BruxApiSyncService $sync, WordPressPushService $push, FieldOwnership $ownership, ContactCategoryRepository $categories, AppSettings $settings): Response
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
            'office_email' => $settings->officeEmail(),
        ]);
    }

    #[Route('/verwaltung/einstellungen', name: 'app_settings_save', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function save(Request $request, AppSettings $settings, ValidatorInterface $validator): Response
    {
        if (!$this->isCsrfTokenValid('settings', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Ungültiges Formular. Bitte Seite neu laden.');
        }
        $email = trim($request->request->getString('office_email'));
        if (\count($validator->validate($email, [new NotBlank(), new Email()])) > 0) {
            $this->addFlash('danger', 'Bitte eine gültige E-Mail-Adresse angeben.');
        } else {
            $settings->setOfficeEmail($email);
            $this->addFlash('success', 'Gespeichert: Antworten der Techniker:innen gehen an ' . $email . '.');
        }

        return $this->redirectToRoute('app_settings', [], Response::HTTP_SEE_OTHER);
    }
}
