<?php

namespace App\Controller;

use App\Entity\EventCategory;
use App\Form\EventCategoryType;
use App\Repository\EventCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/event_category')]
#[IsGranted('ROLE_USER')]
class EventCategoryController extends AbstractController
{
    #[Route('/', name: 'app_event_category_index', methods: ['GET'])]
    public function index(EventCategoryRepository $repository): Response
    {
        return $this->render('event_category/index.html.twig', [
            'event_categories' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_event_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $event_category = new EventCategory();
        $form = $this->createForm(EventCategoryType::class, $event_category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($event_category);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_event_category_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('event_category/detail.html.twig', [
            'event_category' => $event_category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EventCategory $event_category, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(EventCategoryType::class, $event_category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($event_category);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_event_category_edit', ['id' => $event_category->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('event_category/detail.html.twig', [
            'event_category' => $event_category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_category_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, EventCategory $event_category, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($event_category);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_event_category_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_event_category_edit', ['id' => $event_category->getId()]);
        }
    }
}
