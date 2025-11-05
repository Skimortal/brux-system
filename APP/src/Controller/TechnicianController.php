<?php

namespace App\Controller;

use App\Entity\Technician;
use App\Form\TechnicianType;
use App\Repository\TechnicianRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/technician')]
#[IsGranted('ROLE_USER')]
class TechnicianController extends AbstractController
{
    #[Route('/', name: 'app_technician_index', methods: ['GET'])]
    public function index(TechnicianRepository $repository): Response
    {
        return $this->render('technician/index.html.twig', [
            'technicians' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_technician_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $technician = new Technician();
        $form = $this->createForm(TechnicianType::class, $technician);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($technician);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_technician_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('technician/detail.html.twig', [
            'technician' => $technician,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_technician_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Technician $technician, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(TechnicianType::class, $technician);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($technician);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_technician_edit', ['id' => $technician->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('technician/detail.html.twig', [
            'technician' => $technician,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_technician_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Technician $technician, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($technician);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_technician_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_technician_edit', ['id' => $technician->getId()]);
        }
    }
}
