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
        RoomRepository $roomRepo
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

            // Raum-Filterung:
            // - Wenn roomId gesetzt ist UND Appointment hat einen Raum:
            //   Nur anzeigen wenn es der gleiche Raum ist
            // - Wenn Appointment KEINEN Raum hat: Auf ALLEN Kalendern anzeigen
            if ($roomId) {
                if ($appRoom !== null && $appRoom->getId() != $roomId) {
                    continue; // Anderer Raum -> überspringen
                }
                // Falls appRoom === null -> anzeigen (global)
            }

            // Typ-Bestimmung & Kategorien-Filterung
            $type = 'private';
            $color = $app->getColor() ?? '#9e9e9e';

            if ($app->getCleaning()) {
                $type = 'cleaning';
                if (!in_array('cleaning', $filters)) continue;
            } elseif ($app->getTechnician()) {
                $type = 'technician';
                if (!in_array('technician', $filters)) continue;
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
                $app->getTitle(),
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
            $eventData['extendedProps']['type'] = $type;

            $events[] = $eventData;
        }

        // 2. Production Events laden (wenn production Filter aktiv)
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

                // Raum-Filterung: Gleiche Logik wie bei Appointments
                if ($roomId) {
                    if ($peRoom !== null && $peRoom->getId() != $roomId) {
                        continue;
                    }
                }

                // Event bauen
                $title = $pe->getProduction() ? $pe->getProduction()->getTitle() : 'Produktion';

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
                    // Default: 2 Stunden später
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
                    'color' => '#f4b400', // Orange/Gelb für Productions
                    'backgroundColor' => '#f4b400',
                    'borderColor' => '#f4b400',
                    'extendedProps' => [
                        'type' => 'production',
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

    #[Route('/appointment/create', name: 'app_appointment_create', methods: ['POST'])]
    public function createAppointment(
        Request $request,
        EntityManagerInterface $em,
        RoomRepository $roomRepository,
        CleaningRepository $cleanRepo,
        TechnicianRepository $techRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $appointment = new Appointment();
        $appointment->setTitle($data['title'] ?? 'Neuer Termin');
        $appointment->setDescription($data['description'] ?? null);

        // Raum setzen (kann null sein für globale Events)
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
        $appointment->setColor($data['color'] ?? '#4285f4');

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
        TechnicianRepository $techRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $appointment->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $appointment->setDescription($data['description']);
        }

        // Raum Update (kann auch auf null gesetzt werden)
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
        if (isset($data['color'])) {
            $appointment->setColor($data['color']);
        }

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
            if ($appointment->getCleaning()) $type = 'cleaning';
            elseif ($appointment->getTechnician()) $type = 'technician';

            return [
                'id' => $appointment->getId(),
                'title' => $appointment->getTitle(),
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
                ]
            ];
        }, $appointments);

        return $this->json($events);
    }
}
