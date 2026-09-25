<?php

namespace App\Controller;

use App\Entity\Production;
use App\Entity\ProductionEvent;
use App\Form\ProductionEventType;
use App\Repository\ProductionEventRepository;
use App\Service\WordPressPushService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/production-event')]
#[IsGranted('ROLE_USER')]
class ProductionEventController extends AbstractController
{
    #[Route('/', name: 'app_production_event_index', methods: ['GET'])]
    public function index(ProductionEventRepository $repository): Response
    {
        return $this->render('production_event/index.html.twig', [
            'production_events' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_production_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t, WordPressPushService $wordPressPush): Response
    {
        $productionEvent = new ProductionEvent();

        $productionId = $request->query->get('production');
        if ($productionId) {
            $production = $entityManager->getRepository(Production::class)->find($productionId);
            if ($production) {
                $productionEvent->setProduction($production);
            }
        }

        $form = $this->createForm(ProductionEventType::class, $productionEvent, [
            'production' => $productionEvent->getProduction(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // eventIndex automatisch vergeben: nächster freier Index pro Produktion
                if ($productionEvent->getEventIndex() === null && $productionEvent->getProduction()) {
                    $maxIndex = -1;
                    foreach ($productionEvent->getProduction()->getEvents() as $existingEvent) {
                        if ($existingEvent->getEventIndex() !== null && $existingEvent->getEventIndex() > $maxIndex) {
                            $maxIndex = $existingEvent->getEventIndex();
                        }
                    }
                    $productionEvent->setEventIndex($maxIndex + 1);
                }

                $entityManager->persist($productionEvent);
                $entityManager->flush();

                $wordPressPush->pushProductionEvent($productionEvent);

                $this->addFlash('success', $t->trans('production_event.created_successfully'));

                if ($productionEvent->getProduction()) {
                    return $this->redirectToRoute('app_production_edit', ['id' => $productionEvent->getProduction()->getId()]);
                }

                return $this->redirectToRoute('app_production_event_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('production_event/detail.html.twig', [
            'production_event' => $productionEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_production_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProductionEvent $productionEvent, EntityManagerInterface $entityManager, TranslatorInterface $t, WordPressPushService $wordPressPush): Response
    {
        $form = $this->createForm(ProductionEventType::class, $productionEvent, [
            'production' => $productionEvent->getProduction(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($productionEvent);
                $entityManager->flush();

                $wordPressPush->pushProductionEvent($productionEvent);

                $this->addFlash('success', $t->trans('production_event.updated_successfully'));

                if ($productionEvent->getProduction()) {
                    return $this->redirectToRoute('app_production_edit', ['id' => $productionEvent->getProduction()->getId()]);
                }

                return $this->redirectToRoute('app_production_event_edit', ['id' => $productionEvent->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('production_event/detail.html.twig', [
            'production_event' => $productionEvent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_production_event_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, ProductionEvent $productionEvent, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($productionEvent);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('production_event.deleted_successfully'));
            return $this->redirectToRoute('app_production_event_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_production_event_edit', ['id' => $productionEvent->getId()]);
        }
    }
}
