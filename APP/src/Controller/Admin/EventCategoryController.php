<?php

namespace App\Controller\Admin;

use App\Controller\CrudTrait;
use App\Entity\EventCategory;
use App\Form\EventCategoryType;
use App\Repository\EventCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/verwaltung/event-kategorien')]
#[IsGranted('ROLE_ADMIN')]
final class EventCategoryController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_event_category_index', methods: ['GET'])]
    public function index(EventCategoryRepository $categories): Response
    {
        return $this->render('crud/list.html.twig', [
            'title' => 'Event-Kategorien',
            'subtitle' => 'Kommen aus WordPress und werden beim Import aktualisiert.',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')]],
            'new_url' => null,
            'columns' => ['Name', 'Slug', 'WordPress-ID'],
            'rows' => array_map(fn (EventCategory $c) => [
                'url' => $this->generateUrl('app_event_category_edit', ['id' => $c->getId()]),
                'cells' => [$c->getName(), $c->getSlug() ?? '–', $c->getExternalId() ?? '–'],
            ], $categories->findBy([], ['name' => 'ASC'])),
        ]);
    }

    #[Route('/{id}', name: 'app_event_category_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(EventCategory $category, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventCategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Kategorie gespeichert.');

            return $this->redirectToRoute('app_event_category_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $category->getName(),
            'intro' => 'Hinweis: Änderungen werden beim nächsten WordPress-Import überschrieben.',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')], ['label' => 'Event-Kategorien', 'url' => $this->generateUrl('app_event_category_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_event_category_index'),
            'delete_url' => $this->generateUrl('app_event_category_delete', ['id' => $category->getId()]),
            'delete_token' => 'delete-event-category-' . $category->getId(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/loeschen', name: 'app_event_category_delete', methods: ['POST'])]
    public function delete(EventCategory $category, Request $request, EntityManagerInterface $em): Response
    {
        return $this->deleteEntity($request, $em, $category, 'delete-event-category-' . $category->getId(), 'app_event_category_index', 'Kategorie');
    }
}
