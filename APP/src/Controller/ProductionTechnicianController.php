<?php

namespace App\Controller;

use App\Entity\ProductionTechnician;
use App\Form\ProductionTechnicianType;
use App\Repository\ProductionTechnicianRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/production-technician')]
#[IsGranted('ROLE_USER')]
class ProductionTechnicianController extends AbstractController
{
    #[Route('/', name: 'app_production_technician_index', methods: ['GET'])]
    public function index(ProductionTechnicianRepository $repository): Response
    {
        return $this->render('production_technician/index.html.twig', [
            'production_technicians' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_production_technician_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $productionTechnician = new ProductionTechnician();
        $form = $this->createForm(ProductionTechnicianType::class, $productionTechnician);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($productionTechnician);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_production_technician_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('production_technician/detail.html.twig', [
            'production_technician' => $productionTechnician,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_production_technician_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProductionTechnician $productionTechnician, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(ProductionTechnicianType::class, $productionTechnician);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($productionTechnician);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_production_technician_edit', ['id' => $productionTechnician->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('production_technician/detail.html.twig', [
            'production_technician' => $productionTechnician,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_production_technician_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, ProductionTechnician $productionTechnician, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($productionTechnician);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_production_technician_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_production_technician_edit', ['id' => $productionTechnician->getId()]);
        }
    }
}
