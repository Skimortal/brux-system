<?php

namespace App\Controller;

use App\Entity\Cleaning;
use App\Form\CleaningType;
use App\Repository\CleaningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/cleaning')]
#[IsGranted('ROLE_USER')]
class CleaningController extends AbstractController
{
    #[Route('/', name: 'app_cleaning_index', methods: ['GET'])]
    public function index(CleaningRepository $repository): Response
    {
        return $this->render('cleaning/index.html.twig', [
            'cleanings' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_cleaning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $cleaning = new Cleaning();
        $form = $this->createForm(CleaningType::class, $cleaning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($cleaning);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_cleaning_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('cleaning/detail.html.twig', [
            'cleaning' => $cleaning,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cleaning_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cleaning $cleaning, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(CleaningType::class, $cleaning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($cleaning);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_cleaning_edit', ['id' => $cleaning->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('cleaning/detail.html.twig', [
            'cleaning' => $cleaning,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cleaning_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Cleaning $cleaning, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($cleaning);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_cleaning_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_cleaning_edit', ['id' => $cleaning->getId()]);
        }
    }
}
