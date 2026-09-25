<?php

namespace App\Controller;

use App\Entity\Production;
use App\Form\ProductionType;
use App\Repository\ProductionRepository;
use App\Service\WordPressPushService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/produktionen')]
final class ProductionController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_production_index', methods: ['GET'])]
    public function index(Request $request, ProductionRepository $productions): Response
    {
        $showAll = $request->query->getBoolean('alle');
        $term = trim($request->query->getString('q'));

        return $this->render('production/index.html.twig', [
            'rows' => $productions->findOverview($showAll, $term),
            'show_all' => $showAll,
            'term' => $term,
        ]);
    }

    #[Route('/neu', name: 'app_production_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, WordPressPushService $push): Response
    {
        return $this->edit(new Production(), $request, $em, $push);
    }

    #[Route('/{id}', name: 'app_production_show', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Production $production, Request $request, EntityManagerInterface $em, WordPressPushService $push): Response
    {
        $form = $this->createForm(ProductionType::class, $production);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->keepSingleMainContact($production);
            $em->persist($production);
            $em->flush();
            $result = $push->pushProduction($production);
            if ($result['status'] === 'error') {
                $this->addFlash('warning', 'Gespeichert, aber die Übertragung an die Website ist fehlgeschlagen: ' . ($result['message'] ?? ''));
            } else {
                $this->addFlash('success', 'Produktion gespeichert.');
            }

            return $this->redirectToRoute('app_production_show', ['id' => $production->getId()], 303);
        }

        $performances = $production->getEvents()->toArray();
        usort($performances, static fn ($a, $b) => [$a->getDate(), $a->getTimeFrom()] <=> [$b->getDate(), $b->getTimeFrom()]);

        return $this->render('production/show.html.twig', [
            'production' => $production,
            'form' => $form,
            'performances' => $performances,
            'today' => new \DateTimeImmutable('today'),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    #[Route('/{id}/loeschen', name: 'app_production_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Production $production, Request $request, EntityManagerInterface $em): Response
    {
        return $this->deleteEntity($request, $em, $production, 'delete-production-' . $production->getId(), 'app_production_index', 'Produktion');
    }

    /** Only one contact person can be the main contact; the last one ticked wins. */
    private function keepSingleMainContact(Production $production): void
    {
        $main = null;
        foreach ($production->getContactPersons() as $person) {
            if ($person->isHauptansprechperson()) {
                $main?->setHauptansprechperson(false);
                $main = $person;
            }
        }
    }
}
