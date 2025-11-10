<?php
namespace App\Controller;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController {

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('home/dashboard.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/appointment/create', name: 'app_appointment_create', methods: ['POST'])]
    public function createAppointment(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $appointment = new Appointment();
        $appointment->setTitle($data['title'] ?? 'Neuer Termin');
        $appointment->setDescription($data['description'] ?? null);

        $startDate = new \DateTime($data['start']);
        $endDate = new \DateTime($data['end']);
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
            ];
        }, $appointments);

        return $this->json($events);
    }

}
