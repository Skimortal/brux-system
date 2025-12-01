<?php

namespace App\Controller;

use App\DTO\CalendarEventDto;
use App\Entity\Appointment;
use App\Entity\KeyManagement;
use App\Entity\Room;
use App\Enum\KeyStatus;
use App\Repository\AppointmentRepository;
use App\Repository\CleaningRepository;
use App\Repository\KeyManagementRepository;
use App\Repository\ProductionEventRepository;
use App\Repository\ProductionRepository;
use App\Repository\RoomRepository;
use App\Repository\TechnicianRepository;
use App\Service\BruxApiSyncService;
use App\Service\CalendarColorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(
        RoomRepository $roomRepository,
        KeyManagementRepository $keyRepository,
        \App\Repository\UserRepository $userRepo,
        \App\Repository\TechnicianRepository $techRepo,
        \App\Repository\ProductionRepository $prodRepo,
        \App\Repository\CleaningRepository $cleanRepo
    ): Response
    {
        // 1. Nur Räume laden, die angezeigt werden sollen
        $rooms = $roomRepository->findBy(['showOnDashboard' => true]);

        // 2. ALLE Schlüssel laden, gruppiert nach Raum
        $allKeys = $keyRepository->findAll();

        // 3. Schlüssel nach Raum gruppieren
        $keysByRoom = [];
        $keysWithoutRoom = [];

        foreach ($allKeys as $key) {
            if ($key->getRoom()) {
                $keysByRoom[$key->getRoom()->getId()][] = $key;
            } else {
                $keysWithoutRoom[] = $key;
            }
        }

        return $this->render('home/dashboard.html.twig', [
            'user' => $this->getUser(),
            'rooms' => $rooms,
            'allRooms' => $roomRepository->findAll(), // NEU: Alle Räume für Dropdown
            'keysByRoom' => $keysByRoom,
            'keysWithoutRoom' => $keysWithoutRoom,
            'allUsers' => $userRepo->findAll(),
            'allTechnicians' => $techRepo->findAll(),
            'allProductions' => $prodRepo->findAll(),
            'allCleanings' => $cleanRepo->findAll(),
        ]);
    }

    #[Route('/dashboard/events', name: 'app_dashboard_events', methods: ['GET'])]
    public function getDashboardEvents(
        Request $request,
        AppointmentRepository $appointmentRepo,
        CleaningRepository $cleaningRepo,
        ProductionRepository $productionRepo,
        ProductionEventRepository $productionEventRepo,
        TechnicianRepository $techRepo,
        RoomRepository $roomRepo,
        KeyManagementRepository $keyManagementRepo,
        CalendarColorService $colorService
    ): JsonResponse
    {
        $startStr = $request->query->get('start');
        $endStr = $request->query->get('end');
        $roomId = $request->query->get('roomId');

        // Filter als Array
        $filters = explode(',', $request->query->get('filters', ''));

        // Validierung der Datumsangaben
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

        // 1. Appointments laden
        $appointments = $appointmentRepo->findAllForCalendar($start, $end);

        foreach ($appointments as $app) {
            $appRoom = $app->getRoom();

            // Raum-Filterung
            if ($roomId) {
                if ($appRoom !== null && $appRoom->getId() != $roomId) {
                    continue;
                }
            }

            // Typ-Bestimmung & Kategorien-Filterung
            $type = 'private';
            $color = $colorService->getAppointmentColor(
                $app->getCleaning(),
                $app->getTechnician(),
                $app->getProduction()
            );

            // Titel zusammensetzen mit Relation
            $displayTitle = $app->getTitle();

            if ($app->getProduction()) {
                $type = 'production';
                // Verwende Nuance für Produktions-Appointments
                $color = $colorService->getProductionAppointmentNuance($app->getProduction()->getId());
                if (!in_array('production', $filters)) continue;

                // Titel: "Mein Titel (Produktionsname)"
                $displayTitle .= ' (' . $app->getProduction()->__toString() . ')';
            } elseif ($app->getCleaning()) {
                $type = 'cleaning';
                if (!in_array('cleaning', $filters)) continue;

                // Titel: "Mein Titel (Reinigungsname)"
                $displayTitle .= ' (' . $app->getCleaning()->__toString() . ')';
            } elseif ($app->getTechnician()) {
                $type = 'technician';
                if (!in_array('technician', $filters)) continue;

                // Titel: "Mein Titel (Technikername)"
                $displayTitle .= ' (' . $app->getTechnician()->__toString() . ')';
            } else {
                if (!in_array('private', $filters)) continue;
            }

            // End-Datum für FullCalendar anpassen
            $startDate = clone $app->getStartDate();
            $endDate = clone $app->getEndDate();
            if ($app->isAllDay()) {
                $endDate->modify('+1 day');
            }

            $eventData = (new CalendarEventDto(
                'appt_' . $app->getId(),
                $displayTitle,  // Erweiterten Titel verwenden
                $startDate->format('c'),
                $endDate->format('c'),
                $type,
                $color,
                $app->isAllDay(),
                $app->getDescription()
            ))->toArray();

            $eventData['extendedProps']['roomId'] = $appRoom ? $appRoom->getId() : null;
            $eventData['extendedProps']['cleaningId'] = $app->getCleaning() ? $app->getCleaning()->getId() : null;
            $eventData['extendedProps']['technicianId'] = $app->getTechnician() ? $app->getTechnician()->getId() : null;
            $eventData['extendedProps']['productionId'] = $app->getProduction() ? $app->getProduction()->getId() : null;
            $eventData['extendedProps']['type'] = $type;
            $eventData['extendedProps']['originalTitle'] = $app->getTitle(); // ORIGINAL Titel ohne toString

            $events[] = $eventData;
        }

        // 2. Production Events laden
        if (in_array('production', $filters)) {
            $productionEvents = $productionEventRepo->createQueryBuilder('pe')
                ->where('pe.date BETWEEN :start AND :end')
                ->andWhere('pe.date IS NOT NULL')
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getResult();

            foreach ($productionEvents as $pe) {
                $peRoom = $pe->getRoom();

                if ($roomId) {
                    if ($peRoom !== null && $peRoom->getId() != $roomId) {
                        continue;
                    }
                }

                $title = $pe->getProduction() ? $pe->getProduction()->getTitle() : 'Produktion';

                // Farbe basierend auf Production-ID
                $color = $pe->getProduction()
                    ? $colorService->getProductionEventColor($pe->getProduction()->getId())
                    : '#E91E63';

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
                        'productionId' => $pe->getProduction() ? $pe->getProduction()->getId() : null,
                        'productionEventId' => $pe->getId(),
                        'roomId' => $peRoom ? $peRoom->getId() : null,
                        'description' => sprintf(
                            'Raum: %s | Status: %s',
                            $peRoom ? $peRoom->getName() : 'Kein Raum',
                            $pe->getStatus() ? $pe->getStatus()->getLabel() : '-'
                        ),
                    ]
                ];

                $events[] = $eventData;
            }
        }

        // 3. NEU: Verliehene Schlüssel laden
        if (in_array('keys', $filters)) {
            $borrowedKeys = $keyManagementRepo->createQueryBuilder('k')
                ->where('k.status = :borrowed')
                ->andWhere('k.borrowDate <= :end')
                ->andWhere('(k.returnDate >= :start OR k.returnDate IS NULL)')
                ->setParameter('borrowed', KeyStatus::BORROWED)
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getResult();

            foreach ($borrowedKeys as $key) {
                $keyRoom = $key->getRoom();

                // Raum-Filterung
                if ($roomId) {
                    if ($keyRoom !== null && $keyRoom->getId() != $roomId) {
                        continue;
                    }
                }

                $borrowDate = $key->getBorrowDate();
                $returnDate = $key->getReturnDate();

                if (!$borrowDate) {
                    continue;
                }

                // Event-Start und -Ende
                $eventStart = clone $borrowDate;
                $eventStart->setTime(0, 0, 0);

                $eventEnd = $returnDate ? clone $returnDate : clone $end;
                $eventEnd->setTime(23, 59, 59);

                // Titel mit Schlüsselname und Inhaber
                $holderName = $key->getCurrentHolderName();
                $title = '🔑 ' . $key->getName() . ' (' . $holderName . ')';

                $events[] = [
                    'id' => 'key_' . $key->getId(),
                    'title' => $title,
                    'start' => $eventStart->format('c'),
                    'end' => $eventEnd->format('c'),
                    'allDay' => true,
                    'color' => '#FF9800',
                    'backgroundColor' => '#FF9800',
                    'borderColor' => '#F57C00',
                    'extendedProps' => [
                        'type' => 'key',
                        'keyId' => $key->getId(),
                        'roomId' => $keyRoom ? $keyRoom->getId() : null,
                        'description' => sprintf(
                            'Schlüssel: %s | Verliehen an: %s | Rückgabe: %s',
                            $key->getName(),
                            $holderName,
                            $returnDate ? $returnDate->format('d.m.Y') : 'Offen'
                        ),
                    ]
                ];
            }
        }

        return $this->json($events);
    }

    #[Route('/dashboard/production-event/{id}/details', name: 'app_dashboard_production_event_details', methods: ['GET'])]
    public function getProductionEventDetails(
        int $id,
        ProductionEventRepository $productionEventRepo,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $event = $productionEventRepo->find($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        $production = $event->getProduction();

        $data = [
            'event' => [
                'id' => $event->getId(),
                'eventIndex' => $event->getEventIndex(),
                'date' => $event->getDate() ? $event->getDate()->format('d.m.Y') : null,
                'timeFrom' => $event->getTimeFrom(),
                'timeTo' => $event->getTimeTo(),
                'room' => $event->getRoom() ? $event->getRoom()->getName() : null,
                'status' => $event->getStatus() ? $translator->trans($event->getStatus()->getLabel()) : null,
                'reservationStatus' => $event->getReservationStatus() ? $translator->trans($event->getReservationStatus()->getLabel()) : null,
                'quota' => $event->getQuota(),
                'incomingTotal' => $event->getIncomingTotal(),
                'freeSeats' => $event->getFreeSeats(),
                'reservationNote' => $event->getReservationNote(),
                'categories' => array_map(fn($cat) => [
                    'name' => $cat->getName(),
                    'slug' => $cat->getSlug()
                ], $event->getCategories()->toArray()),
                'prices' => array_map(fn($price) => [
                    'categoryLabel' => $price->getCategoryLabel(),
                    'priceLabel' => $price->getPriceLabel(),
                    'reservedSeats' => $price->getReservedSeats(),
                    'incomingReservations' => $price->getIncomingReservations()
                ], $event->getPriceList()->toArray()),
            ],
            'production' => $production ? [
                'id' => $production->getId(),
                'title' => $production->getTitle(),
                'permalink' => $production->getPermalink(),
                'postThumbnailUrl' => $production->getPostThumbnailUrl(),
                'contentHtml' => $production->getContentHtml(),
                'excerptHtml' => $production->getExcerptHtml(),
            ] : null
        ];

        return $this->json($data);
    }

    #[Route('/dashboard/key/{id}/update', name: 'app_dashboard_key_update', methods: ['POST'])]
    public function updateKey(
        KeyManagement $key,
        Request $request,
        EntityManagerInterface $em,
        \App\Repository\UserRepository $userRepo,
        \App\Repository\TechnicianRepository $techRepo,
        \App\Repository\ProductionRepository $prodRepo,
        \App\Repository\CleaningRepository $cleanRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) {
            $key->setStatus(KeyStatus::from($data['status']));
        }

        // Reset relations
        $key->setUser(null);
        $key->setTechnician(null);
        $key->setProduction(null);
        $key->setCleaning(null);

        if (!empty($data['userId'])) $key->setUser($userRepo->find($data['userId']));
        if (!empty($data['technicianId'])) $key->setTechnician($techRepo->find($data['technicianId']));
        if (!empty($data['productionId'])) $key->setProduction($prodRepo->find($data['productionId']));
        if (!empty($data['cleaningId'])) $key->setCleaning($cleanRepo->find($data['cleaningId']));

        if (isset($data['borrowDate'])) {
            $key->setBorrowDate(!empty($data['borrowDate']) ? new \DateTime($data['borrowDate']) : null);
        }
        if (isset($data['returnDate'])) {
            $key->setReturnDate(!empty($data['returnDate']) ? new \DateTime($data['returnDate']) : null);
        }

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/dashboard/sync-api', name: 'app_dashboard_sync_api', methods: ['POST'])]
    public function syncBruxApi(BruxApiSyncService $syncService): JsonResponse
    {
        try {
            $stats = $syncService->syncFromApi();

            return $this->json([
                'success' => true,
                'stats' => $stats,
                'message' => sprintf(
                    'Synchronisation erfolgreich! Produktionen: %d erstellt, %d aktualisiert. Events: %d erstellt, %d aktualisiert.',
                    $stats['productions_created'],
                    $stats['productions_updated'],
                    $stats['events_created'],
                    $stats['events_updated']
                )
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Fehler bei der Synchronisation: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/appointment/create', name: 'app_appointment_create', methods: ['POST'])]
    public function createAppointment(
        Request $request,
        EntityManagerInterface $em,
        RoomRepository $roomRepository,
        CleaningRepository $cleanRepo,
        TechnicianRepository $techRepo,
        ProductionRepository $productionRepo,
        CalendarColorService $colorService
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $appointment = new Appointment();
        $appointment->setTitle($data['title'] ?? 'Neuer Termin');
        $appointment->setDescription($data['description'] ?? null);

        // Raum setzen
        if (!empty($data['roomId'])) {
            $room = $roomRepository->find($data['roomId']);
            if ($room) {
                $appointment->setRoom($room);
            }
        }

        // Typ Relationen setzen
        if (!empty($data['cleaningId'])) {
            $appointment->setCleaning($cleanRepo->find($data['cleaningId']));
        }
        if (!empty($data['technicianId'])) {
            $appointment->setTechnician($techRepo->find($data['technicianId']));
        }
        if (!empty($data['productionId'])) {
            $appointment->setProduction($productionRepo->find($data['productionId']));
        }

        try {
            $startDate = new \DateTime($data['start']);
            $endDate = new \DateTime($data['end']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Invalid date format'], 400);
        }

        $allDay = $data['allDay'] ?? false;

        $appointment->setStartDate($startDate);
        $appointment->setEndDate($endDate);
        $appointment->setAllDay($allDay);

        // Farbe automatisch setzen basierend auf Typ
        $color = $colorService->getAppointmentColor(
            $appointment->getCleaning(),
            $appointment->getTechnician(),
            $appointment->getProduction()
        );
        // Bei Production: Nuance verwenden
        if ($appointment->getProduction()) {
            $color = $colorService->getProductionAppointmentNuance($appointment->getProduction()->getId());
        }
        $appointment->setColor($color);

        $em->persist($appointment);
        $em->flush();

        $responseEndDate = clone $appointment->getEndDate();
        if ($allDay) {
            $responseEndDate->modify('+1 day')->setTime(0, 0, 0);
        }

        return $this->json([
            'success' => true,
            'id' => $appointment->getId(),
            'title' => $appointment->getTitle(),
            'start' => $appointment->getStartDate()->format('c'),
            'end' => $responseEndDate->format('c'),
            'allDay' => $appointment->isAllDay(),
            'color' => $appointment->getColor(),
            'roomId' => $appointment->getRoom() ? $appointment->getRoom()->getId() : null,
        ]);
    }

    #[Route('/appointment/{id}/edit', name: 'app_appointment_edit', methods: ['PUT'])]
    public function editAppointment(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $em,
        RoomRepository $roomRepository,
        CleaningRepository $cleanRepo,
        TechnicianRepository $techRepo,
        ProductionRepository $productionRepo,
        CalendarColorService $colorService
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $appointment->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $appointment->setDescription($data['description']);
        }

        // Raum Update
        if (array_key_exists('roomId', $data)) {
            if (!empty($data['roomId'])) {
                $room = $roomRepository->find($data['roomId']);
                $appointment->setRoom($room);
            } else {
                $appointment->setRoom(null);
            }
        }

        // Typ Update
        if (array_key_exists('cleaningId', $data)) {
            $appointment->setCleaning(!empty($data['cleaningId']) ? $cleanRepo->find($data['cleaningId']) : null);
        }
        if (array_key_exists('technicianId', $data)) {
            $appointment->setTechnician(!empty($data['technicianId']) ? $techRepo->find($data['technicianId']) : null);
        }
        if (array_key_exists('productionId', $data)) {
            $appointment->setProduction(!empty($data['productionId']) ? $productionRepo->find($data['productionId']) : null);
        }

        if (isset($data['start'])) {
            $appointment->setStartDate(new \DateTime($data['start']));
        }
        if (isset($data['end'])) {
            $endDate = new \DateTime($data['end']);
            $allDay = $data['allDay'] ?? $appointment->isAllDay();

            if ($allDay) {
                $endDate->modify('-1 day');
                $endDate->setTime(23, 59, 59);
            }

            $appointment->setEndDate($endDate);
        }
        if (isset($data['allDay'])) {
            $oldAllDay = $appointment->isAllDay();
            $newAllDay = $data['allDay'];

            if ($oldAllDay !== $newAllDay) {
                $endDate = $appointment->getEndDate();
                if ($newAllDay) {
                    $endDate->modify('-1 day');
                    $endDate->setTime(23, 59, 59);
                } else {
                    $endDate->modify('+1 day');
                    $endDate->setTime(10, 0, 0);
                }
                $appointment->setEndDate($endDate);
            }

            $appointment->setAllDay($newAllDay);
        }

        // Farbe automatisch neu setzen
        $color = $colorService->getAppointmentColor(
            $appointment->getCleaning(),
            $appointment->getTechnician(),
            $appointment->getProduction()
        );
        if ($appointment->getProduction()) {
            $color = $colorService->getProductionAppointmentNuance($appointment->getProduction()->getId());
        }
        $appointment->setColor($color);

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/appointment/{id}/delete', name: 'app_appointment_delete', methods: ['DELETE'])]
    public function deleteAppointment(Appointment $appointment, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($appointment);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/appointments/all', name: 'app_appointments_all', methods: ['GET'])]
    public function getAllAppointments(AppointmentRepository $appointmentRepository): JsonResponse
    {
        $appointments = $appointmentRepository->findAllForCalendar();

        $events = array_map(function (Appointment $appointment) {
            $endDate = clone $appointment->getEndDate();

            if ($appointment->isAllDay()) {
                $endDate->modify('+1 day')->setTime(0, 0, 0);
            }

            $type = 'private';
            $displayTitle = $appointment->getTitle();

            if ($appointment->getProduction()) {
                $type = 'production';
                $displayTitle .= ' (' . $appointment->getProduction()->__toString() . ')';
            } elseif ($appointment->getCleaning()) {
                $type = 'cleaning';
                $displayTitle .= ' (' . $appointment->getCleaning()->__toString() . ')';
            } elseif ($appointment->getTechnician()) {
                $type = 'technician';
                $displayTitle .= ' (' . $appointment->getTechnician()->__toString() . ')';
            }

            return [
                'id' => $appointment->getId(),
                'title' => $displayTitle,
                'start' => $appointment->getStartDate()->format('Y-m-d\TH:i:s'),
                'end' => $endDate->format('Y-m-d\TH:i:s'),
                'allDay' => $appointment->isAllDay(),
                'color' => $appointment->getColor(),
                'description' => $appointment->getDescription(),
                'extendedProps' => [
                    'type' => $type,
                    'roomId' => $appointment->getRoom() ? $appointment->getRoom()->getId() : null,
                    'cleaningId' => $appointment->getCleaning() ? $appointment->getCleaning()->getId() : null,
                    'technicianId' => $appointment->getTechnician() ? $appointment->getTechnician()->getId() : null,
                    'productionId' => $appointment->getProduction() ? $appointment->getProduction()->getId() : null,
                    'originalTitle' => $appointment->getTitle(), // ORIGINAL Titel ohne toString
                ]
            ];
        }, $appointments);

        return $this->json($events);
    }
}
