<?php

namespace App\Controller;

use App\Entity\Production;
use App\Form\ProductionType;
use App\Repository\ProductionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/production')]
#[IsGranted('ROLE_USER')]
class ProductionController extends AbstractController
{
    #[Route('/', name: 'app_production_index', methods: ['GET'])]
    public function index(ProductionRepository $repository): Response
    {
        return $this->render('production/index.html.twig', [
            'productions' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_production_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $production = new Production();
        $form = $this->createForm(ProductionType::class, $production);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($production);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('production.created_successfully'));
                return $this->redirectToRoute('app_production_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('production/detail.html.twig', [
            'production' => $production,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_production_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Production $production, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(ProductionType::class, $production);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($production);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('production.updated_successfully'));
                return $this->redirectToRoute('app_production_edit', ['id' => $production->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('production/detail.html.twig', [
            'production' => $production,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_production_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Production $production, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($production);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('production.deleted_successfully'));
            return $this->redirectToRoute('app_production_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_production_edit', ['id' => $production->getId()]);
        }
    }
}
