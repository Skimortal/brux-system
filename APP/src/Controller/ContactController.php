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

#[Route('/kontakte')]
final class ContactController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_contact_index', methods: ['GET'])]
    public function index(Request $request, ContactRepository $contacts, ContactCategoryRepository $categories): Response
    {
        $category = $request->query->getInt('kategorie');
        $term = trim($request->query->getString('q'));
        $list = $contacts->search($term, $category > 0 ? [$category] : []);
        $grouped = [];
        foreach ($list as $contact) {
            $grouped[$contact->getCategory()?->getName() ?? 'Ohne Kategorie'][] = $contact;
        }

        return $this->render('contact/index.html.twig', [
            'grouped' => $grouped,
            'categories' => $categories->findBy([], ['name' => 'ASC']),
            'category' => $category,
            'term' => $term,
            'count' => \count($list),
        ]);
    }

    #[Route('/neu', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ContactCategoryRepository $categories): Response
    {
        $contact = new Contact();
        if ($request->query->getInt('kategorie') > 0) {
            $contact->setCategory($categories->find($request->query->getInt('kategorie')));
        }

        return $this->form($contact, $request, $em);
    }

    #[Route('/{id}', name: 'app_contact_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        return $this->form($contact, $request, $em);
    }

    #[Route('/{id}/loeschen', name: 'app_contact_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        return $this->deleteEntity($request, $em, $contact, 'delete-contact-' . $contact->getId(), 'app_contact_index', 'Kontakt');
    }

    private function form(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($contact);
            $em->flush();
            $this->addFlash('success', 'Kontakt gespeichert.');

            return $this->redirectToRoute('app_contact_index', ['kategorie' => $contact->getCategory()?->getId()], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $contact->getId() ? $contact->getName() : 'Neuer Kontakt',
            'crumbs' => [['label' => 'Kontakte', 'url' => $this->generateUrl('app_contact_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_contact_index'),
            'delete_url' => $contact->getId() && $this->isGranted('ROLE_ADMIN') ? $this->generateUrl('app_contact_delete', ['id' => $contact->getId()]) : null,
            'delete_token' => 'delete-contact-' . $contact->getId(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
