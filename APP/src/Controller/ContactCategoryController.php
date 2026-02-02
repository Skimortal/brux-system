<?php

namespace App\Controller;

use App\Entity\ContactCategory;
use App\Form\ContactCategoryType;
use App\Repository\ContactCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/contact_category')]
#[IsGranted('ROLE_USER')]
class ContactCategoryController extends AbstractController
{
    #[Route('/', name: 'app_contact_category_index', methods: ['GET'])]
    public function index(ContactCategoryRepository $repository): Response
    {
        return $this->render('contact_category/index.html.twig', [
            'contact_categories' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_contact_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $contact_category = new ContactCategory();
        $form = $this->createForm(ContactCategoryType::class, $contact_category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($contact_category);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_contact_category_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('contact_category/detail.html.twig', [
            'contact_category' => $contact_category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contact_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContactCategory $contact_category, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(ContactCategoryType::class, $contact_category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($contact_category);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_contact_category_edit', ['id' => $contact_category->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('contact_category/detail.html.twig', [
            'contact_category' => $contact_category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_category_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, ContactCategory $contact_category, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($contact_category);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_contact_category_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_contact_category_edit', ['id' => $contact_category->getId()]);
        }
    }
}
