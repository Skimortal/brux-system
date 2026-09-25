<?php

namespace App\Controller\Admin;

use App\Controller\CrudTrait;
use App\Entity\ContactCategory;
use App\Form\ContactCategoryType;
use App\Repository\ContactCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/verwaltung/kontaktkategorien')]
#[IsGranted('ROLE_ADMIN')]
final class ContactCategoryController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_contact_category_index', methods: ['GET'])]
    public function index(ContactCategoryRepository $categories): Response
    {
        return $this->render('crud/list.html.twig', [
            'title' => 'Kontaktkategorien',
            'subtitle' => 'Die Rolle legt fest, welche Kontakte im Kalender als Technik, Freiwillige oder Reinigung wählbar sind.',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')]],
            'new_url' => $this->generateUrl('app_contact_category_new'),
            'columns' => ['Name', 'Rolle im Kalender', 'Kontakte'],
            'rows' => array_map(fn (ContactCategory $c) => [
                'url' => $this->generateUrl('app_contact_category_edit', ['id' => $c->getId()]),
                'cells' => [$c->getName(), $c->getRole()?->label() ?? '–', \count($c->getContacts())],
            ], $categories->findBy([], ['name' => 'ASC'])),
        ]);
    }

    #[Route('/neu', name: 'app_contact_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        return $this->form(new ContactCategory(), $request, $em);
    }

    #[Route('/{id}', name: 'app_contact_category_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(ContactCategory $category, Request $request, EntityManagerInterface $em): Response
    {
        return $this->form($category, $request, $em);
    }

    #[Route('/{id}/loeschen', name: 'app_contact_category_delete', methods: ['POST'])]
    public function delete(ContactCategory $category, Request $request, EntityManagerInterface $em): Response
    {
        if (\count($category->getContacts()) > 0) {
            $this->addFlash('danger', 'Die Kategorie enthält noch Kontakte. Bitte zuerst verschieben oder löschen.');

            return $this->redirectToRoute('app_contact_category_edit', ['id' => $category->getId()], 303);
        }

        return $this->deleteEntity($request, $em, $category, 'delete-category-' . $category->getId(), 'app_contact_category_index', 'Kategorie');
    }

    private function form(ContactCategory $category, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContactCategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Kategorie gespeichert.');

            return $this->redirectToRoute('app_contact_category_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $category->getId() ? $category->getName() : 'Neue Kategorie',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')], ['label' => 'Kontaktkategorien', 'url' => $this->generateUrl('app_contact_category_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_contact_category_index'),
            'delete_url' => $category->getId() ? $this->generateUrl('app_contact_category_delete', ['id' => $category->getId()]) : null,
            'delete_token' => 'delete-category-' . $category->getId(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
