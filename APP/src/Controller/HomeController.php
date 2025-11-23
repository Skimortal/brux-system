<?php

namespace App\Controller;

use App\DTO\CalendarEventDto;
use App\Entity\Appointment;
use App\Entity\Room;
use App\Enum\KeyStatus;
use App\Repository\AppointmentRepository;
use App\Repository\CleaningRepository;
use App\Repository\KeyManagementRepository;
use App\Repository\ProductionRepository;
use App\Repository\RoomRepository;
use App\Repository\TechnicianRepository; // Annahme: Existiert oder ähnlicher Name
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        KeyManagementRepository $keyRepository
    ): Response
    {
        // 1. Nur Räume laden, die angezeigt werden sollen
        $rooms = $roomRepository->findBy(['showOnDashboard' => true]);

        // 2. Alle aktiven Schlüssel laden
        // Logik: Rückgabedatum ist leer UND Status ist nicht "Verfügbar"
        $allActiveKeys = $keyRepository->createQueryBuilder('k')
            ->where('k.returnDate IS NULL')
            ->andWhere('k.status != :status')
            ->setParameter('status', KeyStatus::AVAILABLE)
            ->leftJoin('k.room', 'r')
            ->addSelect('r')
            ->getQuery()
            ->getResult();

        // 3. Schlüssel nach Raum gruppieren für einfachere Anzeige im Twig
        $keysByRoom = [];
        foreach ($allActiveKeys as $key) {
            if ($key->getRoom()) {
                $keysByRoom[$key->getRoom()->getId()][] = $key;
            }
        }

        return $this->render('home/dashboard.html.twig', [
            'user' => $this->getUser(),
            'rooms' => $rooms,
            'keysByRoom' => $keysByRoom
        ]);
    }

    #[Route('/dashboard/events', name: 'app_dashboard_events', methods: ['GET'])]
    public function getDashboardEvents(
        Request $request,
        AppointmentRepository $appointmentRepo,
        CleaningRepository $cleaningRepo,
        ProductionRepository $productionRepo,
        // TechnicianRepository $techRepo, // Hier einkommentieren, wenn Repository existiert
        RoomRepository $roomRepo
    ): JsonResponse
    {
        $startStr = $request->query->get('start');
        $endStr = $request->query->get('end');
        $roomId = $request->query->get('roomId');

        // Filter als Array (z.B. ?filters=production,private)
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

        // --- 1. Private Termine (User) ---
        // Filter: 'private'
        if (in_array('private', $filters)) {
            // Hinweis: Hier laden wir globale Termine. Falls Termine räumlich gebunden sein sollen,
            // müsste man $appointmentRepo->findByRoom(...) nutzen.
            $appointments = $appointmentRepo->findAllForCalendar($start, $end);

            foreach ($appointments as $app) {
                $events[] = (new CalendarEventDto(
                    'appt_' . $app->getId(),
                    $app->getTitle(),
                    $app->getStartDate()->format('c'),
                    $app->getEndDate()->format('c'),
                    'private',
                    $app->getColor() ?? '#9e9e9e',
                    $app->isAllDay(),
                    $app->getDescription()
                ))->toArray();
            }
        }

        // --- Raum-spezifische Events ---
        if ($roomId) {
            $room = $roomRepo->find($roomId);

            if ($room) {
                // --- 2. Reinigung ---
                // Filter: 'cleaning'
                if (in_array('cleaning', $filters)) {
                    // TODO: Implementieren Sie hier die Logik für Ihre Cleaning Entity
                    // Beispiel:
                    // $cleanings = $cleaningRepo->findByRoomAndDate($room, $start, $end);
                    // foreach ($cleanings as $c) {
                    //     $events[] = (new CalendarEventDto(
                    //         'clean_' . $c->getId(),
                    //         'Reinigung', // oder $c->getName()
                    //         $c->getStart()->format('c'),
                    //         $c->getEnd()->format('c'),
                    //         'cleaning',
                    //         '#0dcaf0' // Cyan
                    //     ))->toArray();
                    // }
                }

                // --- 3. Produktion ---
                // Filter: 'production'
                if (in_array('production', $filters)) {
                    // TODO: Implementieren Sie hier die Logik für Ihre Production Entity
                    // Beispiel:
                    // $productions = $productionRepo->findByRoomAndDate($room, $start, $end);
                    // foreach ($productions as $p) {
                    //     $events[] = (new CalendarEventDto(
                    //         'prod_' . $p->getId(),
                    //         $p->getName(),
                    //         $p->getStart()->format('c'),
                    //         $p->getEnd()->format('c'),
                    //         'production',
                    //         '#198754', // Green
                    //         false,
                    //         $p->getDescription()
                    //     ))->toArray();
                    // }
                }

                // --- 4. Techniker ---
                // Filter: 'technician'
                if (in_array('technician', $filters)) {
                    // TODO: Implementieren Sie hier die Logik für Technician/ProductionTechnician
                }
            }
        }

        return $this->json($events);
    }

    #[Route('/appointment/create', name: 'app_appointment_create', methods: ['POST'])]
    public function createAppointment(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $appointment = new Appointment();
        $appointment->setTitle($data['title'] ?? 'Neuer Termin');
        $appointment->setDescription($data['description'] ?? null);

        try {
            $startDate = new \DateTime($data['start']);
            $endDate = new \DateTime($data['end']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Invalid date format'], 400);
        }

        $allDay = $data['allDay'] ?? false;

        // Für ganztägige Events: Subtrahiere einen Tag vom End-Datum für die Speicherung
        if ($allDay) {
            $endDate->modify('-1 day');
            $endDate->setTime(23, 59, 59);
        }

        $appointment->setStartDate($startDate);
        $appointment->setEndDate($endDate);
        $appointment->setAllDay($allDay);
        $appointment->setColor($data['color'] ?? '#4285f4');

        $em->persist($appointment);
        $em->flush();

        // Für die Rückgabe: Bei ganztägigen Events wieder einen Tag hinzufügen
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
        ]);
    }

    #[Route('/appointment/{id}/edit', name: 'app_appointment_edit', methods: ['PUT'])]
    public function editAppointment(Appointment $appointment, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $appointment->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $appointment->setDescription($data['description']);
        }
        if (isset($data['start'])) {
            $appointment->setStartDate(new \DateTime($data['start']));
        }
        if (isset($data['end'])) {
            $endDate = new \DateTime($data['end']);
            $allDay = $data['allDay'] ?? $appointment->isAllDay();

            // Für ganztägige Events: Subtrahiere einen Tag vom End-Datum
            if ($allDay) {
                $endDate->modify('-1 day');
                $endDate->setTime(23, 59, 59);
            }

            $appointment->setEndDate($endDate);
        }
        if (isset($data['allDay'])) {
            $oldAllDay = $appointment->isAllDay();
            $newAllDay = $data['allDay'];

            // Wenn sich der allDay-Status ändert, passe das End-Datum an
            if ($oldAllDay !== $newAllDay) {
                $endDate = $appointment->getEndDate();
                if ($newAllDay) {
                    // Von zeitbasiert zu ganztägig: Subtrahiere einen Tag
                    $endDate->modify('-1 day');
                    $endDate->setTime(23, 59, 59);
                } else {
                    // Von ganztägig zu zeitbasiert: Füge einen Tag hinzu
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

            // Für ganztägige Events: Füge einen Tag zum End-Datum hinzu für FullCalendar
            if ($appointment->isAllDay()) {
                $endDate->modify('+1 day')->setTime(0, 0, 0);
            }

            return [
                'id' => $appointment->getId(),
                'title' => $appointment->getTitle(),
                'start' => $appointment->getStartDate()->format('Y-m-d\TH:i:s'),
                'end' => $endDate->format('Y-m-d\TH:i:s'),
                'allDay' => $appointment->isAllDay(),
                'color' => $appointment->getColor(),
                'description' => $appointment->getDescription(),
                // extendedProps für Filterung im Frontend
                'extendedProps' => [
                    'type' => 'private'
                ]
            ];
        }, $appointments);

        return $this->json($events);
    }
}
