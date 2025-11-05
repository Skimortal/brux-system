<?php

namespace App\Controller;

use App\Entity\KeyManagement;
use App\Form\KeyManagementType;
use App\Repository\KeyManagementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/key-management')]
#[IsGranted('ROLE_USER')]
class KeyManagementController extends AbstractController
{
    #[Route('/', name: 'app_key_management_index', methods: ['GET'])]
    public function index(KeyManagementRepository $repository): Response
    {
        return $this->render('key_management/index.html.twig', [
            'keys' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_key_management_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $keyManagement = new KeyManagement();
        $form = $this->createForm(KeyManagementType::class, $keyManagement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($keyManagement);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_key_management_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('key_management/detail.html.twig', [
            'key_management' => $keyManagement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_key_management_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, KeyManagement $keyManagement, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(KeyManagementType::class, $keyManagement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($keyManagement);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_key_management_edit', ['id' => $keyManagement->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('key_management/detail.html.twig', [
            'key_management' => $keyManagement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_key_management_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, KeyManagement $keyManagement, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($keyManagement);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_key_management_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_key_management_edit', ['id' => $keyManagement->getId()]);
        }
    }
}
