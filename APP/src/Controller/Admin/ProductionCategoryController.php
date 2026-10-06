<?php

namespace App\Controller\Admin;

use App\Entity\ProductionCategory;
use App\Form\ProductionCategoryType;
use App\Repository\ProductionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/verwaltung/produktions-kategorien')]
#[IsGranted('ROLE_ADMIN')]
final class ProductionCategoryController extends AbstractController
{
    #[Route('', name: 'app_production_category_index', methods: ['GET'])]
    public function index(ProductionCategoryRepository $categories): Response
    {
        return $this->render('crud/list.html.twig', [
            'title' => 'Produktions-Kategorien',
            'subtitle' => 'Kommen aus WordPress (z. B. VIP, Kinderprogramm). Die Farbe gilt im Kalender; ist hier keine gesetzt, wird die Farbe aus WordPress übernommen.',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')]],
            'new_url' => null,
            'columns' => ['Name', 'Farbe', 'Slug'],
            'rows' => array_map(fn (ProductionCategory $c) => [
                'url' => $this->generateUrl('app_production_category_edit', ['id' => $c->getId()]),
                'cells' => [$c->getName(), $c->getColor() ?? '–', $c->getSlug() ?? '–'],
            ], $categories->findBy([], ['name' => 'ASC'])),
        ]);
    }

    #[Route('/{id}', name: 'app_production_category_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(ProductionCategory $category, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductionCategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Kategorie gespeichert.');

            return $this->redirectToRoute('app_production_category_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $category->getName(),
            'intro' => 'Der Name wird beim nächsten WordPress-Import überschrieben, die Farbe bleibt.',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')], ['label' => 'Produktions-Kategorien', 'url' => $this->generateUrl('app_production_category_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_production_category_index'),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
