<?php

namespace App\Controller;

use App\Entity\Production;
use App\Form\ProductionType;
use App\Repository\ProductionEventRepository;
use App\Repository\ProductionRepository;
use App\Service\CalendarColorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/production')]
#[IsGranted('ROLE_USER')]
class ProductionController extends AbstractController
{
    #[Route('/', name: 'app_production_index', methods: ['GET'])]
    public function index(ProductionRepository $repository): Response
    {
        return $this->render('production/index.html.twig', [
            'productions' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_production_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $production = new Production();
        $form = $this->createForm(ProductionType::class, $production);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($production);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('production.created_successfully'));
                return $this->redirectToRoute('app_production_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('production/detail.html.twig', [
            'production' => $production,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_production_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Production $production, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(ProductionType::class, $production);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Hauptansprechperson-Logik: nur eine Person kann Hauptansprechperson sein
                foreach ($production->getContactPersons() as $contactPerson) {
                    if ($contactPerson->isHauptansprechperson()) {
                        // Alle anderen Hauptansprechpersonen deaktivieren
                        foreach ($production->getContactPersons() as $otherPerson) {
                            if ($otherPerson !== $contactPerson && $otherPerson->isHauptansprechperson()) {
                                $otherPerson->setHauptansprechperson(false);
                            }
                        }
                        break;
                    }
                }

                $entityManager->persist($production);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('production.updated_successfully'));
                return $this->redirectToRoute('app_production_edit', ['id' => $production->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('production/detail.html.twig', [
            'production' => $production,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_production_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Production $production, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($production);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('production.deleted_successfully'));
            return $this->redirectToRoute('app_production_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_production_edit', ['id' => $production->getId()]);
        }
    }

    #[Route('/{id}/events', name: 'app_production_events_json', methods: ['GET'])]
    public function getProductionEvents(
        Production $production,
        Request $request,
        ProductionEventRepository $productionEventRepo,
        CalendarColorService $colorService
    ): JsonResponse
    {
        $startStr = $request->query->get('start');
        $endStr = $request->query->get('end');
        if (!$startStr || !$endStr) {
            return $this->json([]);
        }

        try {
            $start = new \DateTime($startStr);
            $end = new \DateTime($endStr);
        } catch (\Exception $e) {

            return $this->json([]);
        }

        $events = [];

        // Nur Events dieser Produktion laden
        $productionEvents = $productionEventRepo->createQueryBuilder('pe')
            ->where('pe.production = :production')
            ->andWhere('pe.date BETWEEN :start AND :end')
            ->andWhere('pe.date IS NOT NULL')
            ->setParameter('production', $production)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();


        foreach ($productionEvents as $pe) {
            $title = $pe->getProduction() ? $pe->getProduction()->getTitle() : 'Produktion';

            // Farbe basierend auf Production-ID
            $color = $colorService->getProductionEventColor($production->getId());

            // Zeit kombinieren
            $eventStart = clone $pe->getDate();
            if ($pe->getTimeFrom()) {
                $timeParts = explode(':', $pe->getTimeFrom());
                $eventStart->setTime((int)$timeParts[0], (int)($timeParts[1] ?? 0));
            }

            $eventEnd = clone $eventStart;
            if ($pe->getTimeTo()) {
                $timeParts = explode(':', $pe->getTimeTo());
                $eventEnd->setTime((int)$timeParts[0], (int)($timeParts[1] ?? 0));
            } else {
                $eventEnd->modify('+2 hours');
            }

            $isAllDay = empty($pe->getTimeFrom()) && empty($pe->getTimeTo());

            if ($isAllDay) {
                $eventEnd->modify('+1 day');
            }

            $eventData = [
                'id' => 'prod_event_' . $pe->getId(),
                'title' => $title,
                'start' => $eventStart->format('c'),
                'end' => $eventEnd->format('c'),
                'allDay' => $isAllDay,
                'color' => $color,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'type' => 'production_event',
                    'productionId' => $production->getId(),
                    'productionEventId' => $pe->getId(),
                    'roomId' => $pe->getRoom() ? $pe->getRoom()->getId() : null,
                    'roomName' => $pe->getRoom() ? $pe->getRoom()->getName() : 'Kein Raum',
                    'status' => $pe->getStatus() ? $pe->getStatus()->getLabel() : '-',
                    'timeFrom' => $pe->getTimeFrom(),
                    'timeTo' => $pe->getTimeTo(),
                ]
            ];

            $events[] = $eventData;
        }

        return $this->json($events);
    }
}
