<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactCategoryRepository;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/contact')]
#[IsGranted('ROLE_USER')]
class ContactController extends AbstractController
{
    #[Route('/', name: 'app_contact_index', methods: ['GET'])]
    public function index(ContactRepository $repository, ContactCategoryRepository $contactCategoryRepository): Response
    {
        $contacts = $repository->findGroupedByCategory();
        $contact_categories = $contactCategoryRepository->findAll();

        // Group contacts by category
        $groupedContacts = [];
        foreach ($contacts as $contact) {
            $categoryName = $contact->getCategory()?->getName() ?? 'Uncategorized';
            if (!isset($groupedContacts[$categoryName])) {
                $groupedContacts[$categoryName] = [];
            }
            $groupedContacts[$categoryName][] = $contact;
        }

        return $this->render('contact/index.html.twig', [
            'contact_categories' => $contact_categories,
            'grouped_contacts' => $groupedContacts,
        ]);
    }

    #[Route('/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($contact);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        // Für AJAX: nur das Formular zurückgeben
        if ($request->isXmlHttpRequest()) {
            return $this->render('contact/_form.html.twig', [
                'form' => $form,
                'contact' => $contact,
            ]);
        }

        return $this->render('contact/detail.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($contact);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));

                // Für AJAX: Redirect mit Header
                if ($request->isXmlHttpRequest()) {
                    return $this->redirect($this->generateUrl('app_contact_index'));
                }

                return $this->redirectToRoute('app_contact_edit', ['id' => $contact->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        // Für AJAX: nur das Formular zurückgeben
        if ($request->isXmlHttpRequest()) {
            return $this->render('contact/_form.html.twig', [
                'form' => $form,
                'contact' => $contact,
            ]);
        }

        return $this->render('contact/detail.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($contact);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_contact_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_contact_edit', ['id' => $contact->getId()]);
        }
    }
}
