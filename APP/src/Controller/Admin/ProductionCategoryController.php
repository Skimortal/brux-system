<?php

namespace App\Controller\Admin;

use App\Entity\ProductionCategory;
use App\Form\ProductionCategoryType;
use App\Repository\ProductionCategoryRepository;
use App\Service\WordPressPushService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/verwaltung/produktions-kategorien')]
#[IsGranted('ROLE_ADMIN')]
final class ProductionCategoryController extends AbstractController
{
    #[Route('', name: 'app_production_category_index', methods: ['GET'])]
    public function index(ProductionCategoryRepository $categories): Response
    {
        return $this->render('crud/list.html.twig', [
            'title' => 'Produktions-Kategorien',
            'subtitle' => 'Kategorien der Produktionen wie VIP oder Kinderprogramm. Name und Farbe gehen beim Speichern auf die Website, die Farbe gilt auch im Kalender.',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')]],
            'new_url' => $this->generateUrl('app_production_category_new'),
            'columns' => ['Name', 'Farbe', 'Slug'],
            'rows' => array_map(fn (ProductionCategory $c) => [
                'url' => $this->generateUrl('app_production_category_edit', ['id' => $c->getId()]),
                'cells' => [$c->getName(), $c->getColor() !== null ? ['swatch' => $c->getColor(), 'text' => ProductionCategoryType::COLORS[$c->getColor()] ?? $c->getColor()] : '–', $c->getSlug() ?? '–'],
            ], $categories->findBy([], ['name' => 'ASC'])),
        ]);
    }

    #[Route('/neu', name: 'app_production_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, WordPressPushService $push, ProductionCategoryRepository $categories, SluggerInterface $slugger): Response
    {
        return $this->form(new ProductionCategory(), $request, $em, $push, $categories, $slugger);
    }

    #[Route('/{id}', name: 'app_production_category_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(ProductionCategory $category, Request $request, EntityManagerInterface $em, WordPressPushService $push, ProductionCategoryRepository $categories, SluggerInterface $slugger): Response
    {
        return $this->form($category, $request, $em, $push, $categories, $slugger);
    }

    private function form(ProductionCategory $category, Request $request, EntityManagerInterface $em, WordPressPushService $push, ProductionCategoryRepository $categories, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductionCategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $category->getSlug() === null && $category->getName() !== null) {
            // the slug identifies the category in WordPress, so it is fixed once created
            $slug = $slugger->slug($category->getName())->lower()->toString();
            if (str_ends_with($slug, '-event')) {
                $form->get('name')->addError(new FormError('Namen auf „Event“ sind den Kategorien der Vorstellungen vorbehalten.'));
            } elseif ($categories->findOneBy(['slug' => $slug]) !== null) {
                $form->get('name')->addError(new FormError('Eine Kategorie mit diesem Namen gibt es schon.'));
            } else {
                $category->setSlug($slug);
            }
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $result = $push->pushCategory($category);
            if ($result['status'] === 'error') {
                $this->addFlash('warning', 'Gespeichert, aber die Übertragung an die Website ist fehlgeschlagen: ' . ($result['message'] ?? ''));
            } else {
                $this->addFlash('success', 'Kategorie gespeichert.');
            }

            return $this->redirectToRoute('app_production_category_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $category->getId() ? $category->getName() : 'Neue Kategorie',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')], ['label' => 'Produktions-Kategorien', 'url' => $this->generateUrl('app_production_category_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_production_category_index'),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
