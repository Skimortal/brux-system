<?php

namespace App\Controller;

use App\DTO\CalendarEventDto;
use App\Entity\Appointment;
use App\Entity\AppointmentTechnician;
use App\Entity\AppointmentVolunteer;
use App\Entity\KeyManagement;
use App\Entity\ProductionContactPerson;
use App\Entity\ProductionEvent;
use App\Enum\AppointmentStatusEnum;
use App\Enum\AppointmentTypeEnum;
use App\Enum\EventTypeEnum;
use App\Enum\KeyStatus;
use App\Repository\AppointmentRepository;
use App\Repository\CleaningRepository;
use App\Repository\KeyManagementRepository;
use App\Repository\ProductionContactPersonRepository;
use App\Repository\ProductionEventRepository;
use App\Repository\ProductionRepository;
use App\Repository\RoomRepository;
use App\Repository\TechnicianRepository;
use App\Repository\VolunteerRepository;
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
        \App\Repository\CleaningRepository $cleanRepo,
        VolunteerRepository $volunteerRepo
    ): Response
    {
        $rooms = $roomRepository->findBy(['showOnDashboard' => true]);
        $allKeys = $keyRepository->findAll();

        $keysByRoom = [];
        $keysWithoutRoom = [];

        foreach ($allKeys as $key) {
            if ($key->getRoom()) {
                $keysByRoom[$key->getRoom()->getId()][] = $key;
            } else {
                $keysWithoutRoom[] = $key;
            }
        }

        // Daten für JavaScript aufbereiten
        $techniciansData = array_map(function($tech) {
            return [
                'id' => $tech->getId(),
                'name' => $tech->getName(),
                'email' => $tech->getEmail(),
                'phone' => $tech->getPhone()
            ];
        }, $techRepo->findAll());

        $volunteersData = array_map(function($vol) {
            return [
                'id' => $vol->getId(),
                'name' => $vol->getName(),
//                'email' => $vol->getEmail(),
//                'phone' => $vol->getPhone()
            ];
        }, $volunteerRepo->findAll());

        $productionsData = array_map(function($prod) {
            return [
                'id' => $prod->getId(),
                'title' => $prod->getTitle(),
                'displayName' => $prod->getDisplayName()
            ];
        }, $prodRepo->findAll());

        $cleaningsData = array_map(function($clean) {
            return [
                'id' => $clean->getId(),
                'name' => $clean->getName()
            ];
        }, $cleanRepo->findAll());

        return $this->render('home/dashboard.html.twig', [
            'user' => $this->getUser(),
            'rooms' => $rooms,
            'allRooms' => $roomRepository->findAll(),
            'keysByRoom' => $keysByRoom,
            'keysWithoutRoom' => $keysWithoutRoom,
            'allUsers' => $userRepo->findAll(),
            'allTechnicians' => $techRepo->findAll(),
            'allProductions' => $prodRepo->findAll(),
            'allCleanings' => $cleanRepo->findAll(),
            'allVolunteers' => $volunteerRepo->findAll(),
            // JSON-Data für JavaScript
            'techniciansData' => $techniciansData,
            'volunteersData' => $volunteersData,
            'productionsData' => $productionsData,
            'cleaningsData' => $cleaningsData,
        ]);
    }

    #[Route('/dashboard/events', name: 'app_dashboard_events', methods: ['GET'])]
    public function getDashboardEvents(
        Request $request,
        AppointmentRepository $appointmentRepo,
        ProductionEventRepository $productionEventRepo,
        KeyManagementRepository $keyManagementRepo,
        CalendarColorService $colorService,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $startStr = $request->query->get('start');
        $endStr = $request->query->get('end');
        $roomId = $request->query->get('roomId');
        $filters = explode(',', $request->query->get('filters', ''));

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

            if ($roomId && $appRoom && $appRoom->getId() != $roomId) {
                continue;
            }

            // Type Migration: Falls noch NULL oder leer, setze PRIVATE als Default
            $appType = $app->getType();
            if (!$appType) {
                $appType = AppointmentTypeEnum::PRIVATE;
            }

            $typeFilter = $this->mapAppointmentTypeToFilter($appType);
            if (!in_array($typeFilter, $filters)) {
                continue;
            }

            $color = $this->getAppointmentColor($app, $colorService);
            $displayTitle = $this->buildAppointmentTitle($app, $translator);

            $startDate = clone $app->getStartDate();
            $endDate = clone $app->getEndDate();
            if ($app->isAllDay()) {
                $endDate->modify('+1 day');
            }

            $eventData = (new CalendarEventDto(
                'appt_' . $app->getId(),
                $displayTitle,
                $startDate->format('c'),
                $endDate->format('c'),
                $typeFilter,
                $color,
                $app->isAllDay(),
                $app->getDescription()
            ))->toArray();

            $eventData['extendedProps']['technicians'] = array_map(function($appTech) {
                return [
                    'id' => $appTech->getTechnician()->getId(),
                    'name' => $appTech->getTechnician()->getName(),
                    'confirmed' => $appTech->isConfirmed()
                ];
            }, $app->getAppointmentTechnicians()->toArray());

            $eventData['extendedProps']['volunteers'] = array_map(function($appVol) {
                return [
                    'id' => $appVol->getVolunteer()->getId(),
                    'name' => $appVol->getVolunteer()->getName(),
                    'confirmed' => $appVol->isConfirmed(),
                    'tasks' => $appVol->getTasks()
                ];
            }, $app->getAppointmentVolunteers()->toArray());

            $eventData['extendedProps']['roomId'] = $appRoom ? $appRoom->getId() : null;
            $eventData['extendedProps']['cleaningId'] = $app->getCleaning() ? $app->getCleaning()->getId() : null;
            $eventData['extendedProps']['productionId'] = $app->getProduction() ? $app->getProduction()->getId() : null;
            $eventData['extendedProps']['type'] = $typeFilter;
            $eventData['extendedProps']['originalTitle'] = $app->getTitle();
            $eventData['extendedProps']['appointmentType'] = $appType->value;
            $eventData['extendedProps']['eventType'] = $app->getEventType()?->value;
            $eventData['extendedProps']['status'] = $app->getStatus()?->value;
            $eventData['extendedProps']['internalTechniciansAttending'] = $app->isInternalTechniciansAttending();

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

                if ($roomId && $peRoom && $peRoom->getId() != $roomId) {
                    continue;
                }

                $title = $pe->getProduction() ? $pe->getProduction()->getTitle() : 'Produktion';
                $color = $pe->getProduction()
                    ? $colorService->getProductionEventColor($pe->getProduction()->getId())
                    : '#E91E63';

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

                $events[] = [
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
                            $pe->getStatus() ? $translator->trans($pe->getStatus()->getLabel()) : '-'
                        ),
                    ]
                ];
            }
        }

        // 3. Verliehene Schlüssel laden
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

                if ($roomId && $keyRoom && $keyRoom->getId() != $roomId) {
                    continue;
                }

                $borrowDate = $key->getBorrowDate();
                $returnDate = $key->getReturnDate();

                if (!$borrowDate) {
                    continue;
                }

                $eventStart = clone $borrowDate;
                $eventStart->setTime(0, 0, 0);

                $eventEnd = $returnDate ? clone $returnDate : clone $end;
                $eventEnd->setTime(23, 59, 59);

                $isOverdue = $returnDate && $returnDate < new \DateTime();
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
                        'isOverdue' => $isOverdue,
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

    private function mapAppointmentTypeToFilter(AppointmentTypeEnum $type): string
    {
        return match($type) {
            AppointmentTypeEnum::PRIVATE => 'private',
            AppointmentTypeEnum::PRODUCTION => 'production',
            AppointmentTypeEnum::CLOSED_EVENT => 'production',
            AppointmentTypeEnum::SCHOOL_EVENT => 'private',
            AppointmentTypeEnum::INTERNAL => 'private',
            AppointmentTypeEnum::CLEANING => 'cleaning',
        };
    }

    private function getAppointmentColor(Appointment $app, CalendarColorService $colorService): string
    {
        if ($app->getProduction()) {
            return $colorService->getProductionAppointmentNuance($app->getProduction()->getId());
        }

        return $colorService->getAppointmentColor(
            $app->getCleaning(),
            null,
            $app->getProduction()
        );
    }

    private function buildAppointmentTitle(Appointment $app, TranslatorInterface $translator): string
    {
        $icons = [];

        // Event Type Icons
        if ($app->getEventType()) {
            $icons[] = '<i class="' . $app->getEventType()->getIcon() . '"></i>';
        }

        // Techniker Icons
        foreach ($app->getAppointmentTechnicians() as $appTech) {
            $icon = $appTech->isConfirmed()
                ? '<i class="ti-check c-green-500"></i>'
                : '<span style="color: red;">?</span>';
            $icons[] = $icon;
        }

        // Volunteer Icons
        foreach ($app->getAppointmentVolunteers() as $appVol) {
            $icon = $appVol->isConfirmed()
                ? '<i class="ti-thumb-up c-blue-500"></i>'
                : '<i class="ti-thumb-down c-grey-500"></i>';
            $icons[] = $icon;
        }

        $iconString = !empty($icons) ? implode(' ', $icons) . ' ' : '';

        $baseTitle = $app->getTitle();

        return $iconString . $baseTitle;
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

        $productionContactPersons = [];
        if ($production) {
            foreach ($production->getContactPersons() as $cp) {
                $productionContactPersons[] = [
                    'id' => $cp->getId(),
                    'name' => $cp->getName(),
                    'email' => $cp->getEmail(),
                    'phone' => $cp->getPhone(),
                    'hauptansprechperson' => $cp->isHauptansprechperson(),
                ];
            }
        }

        $assignedContactPersonIds = array_map(
            static fn(ProductionContactPerson $cp) => $cp->getId(),
            $event->getContactPersons()->toArray()
        );

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
                'productionContactPersons' => $productionContactPersons,
                'assignedContactPersonIds' => $assignedContactPersonIds,
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

    #[Route('/dashboard/production-event/{id}/contact-persons', name: 'app_dashboard_production_event_contact_persons_update', methods: ['POST'])]
    public function updateProductionEventContactPersons(
        ProductionEvent $event,
        Request $request,
        EntityManagerInterface $em,
        ProductionContactPersonRepository $contactPersonRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $ids = $data['contactPersonIds'] ?? [];
        if (!is_array($ids)) {
            return $this->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        // Reset
        foreach ($event->getContactPersons()->toArray() as $existing) {
            $event->removeContactPerson($existing);
        }

        // Add selected (nur gültige IDs)
        foreach ($ids as $id) {
            if (!$id) continue;
            $cp = $contactPersonRepo->find((int)$id);
            if ($cp) {
                // Optional harte Absicherung: Ansprechpartner muss zur Produktion gehören
                if ($cp->getProduction() && $event->getProduction() && $cp->getProduction()->getId() === $event->getProduction()->getId()) {
                    $event->addContactPerson($cp);
                }
            }
        }

        $em->flush();

        return $this->json(['success' => true]);
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
        ProductionRepository $productionRepo,
        TechnicianRepository $technicianRepo,
        VolunteerRepository $volunteerRepo,
        CalendarColorService $colorService
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $appointment = new Appointment();
        $appointment->setTitle($data['title'] ?? 'Neuer Termin');
        $appointment->setDescription($data['description'] ?? null);

        // Type setzen
        $typeValue = $data['type'] ?? 'private';
        try {
            $appointment->setType(AppointmentTypeEnum::from($typeValue));
        } catch (\ValueError $e) {
            $appointment->setType(AppointmentTypeEnum::PRIVATE);
        }

        // Event Type
        if (!empty($data['eventType'])) {
            try {
                $appointment->setEventType(EventTypeEnum::from($data['eventType']));
            } catch (\ValueError $e) {
                // Ignore
            }
        }

        // Status
        if (!empty($data['status'])) {
            try {
                $appointment->setStatus(AppointmentStatusEnum::from($data['status']));
            } catch (\ValueError $e) {
                // Ignore
            }
        }

        // Internal Technicians
        $appointment->setInternalTechniciansAttending($data['internalTechniciansAttending'] ?? false);

        // Raum
        if (!empty($data['roomId'])) {
            $room = $roomRepository->find($data['roomId']);
            if ($room) {
                $appointment->setRoom($room);
            }
        }

        // Relationen
        if (!empty($data['cleaningId'])) {
            $appointment->setCleaning($cleanRepo->find($data['cleaningId']));
        }
        if (!empty($data['productionId'])) {
            $appointment->setProduction($productionRepo->find($data['productionId']));
        }

        // Datum
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

        // Farbe
        $color = $this->getAppointmentColor($appointment, $colorService);
        $appointment->setColor($color);

        // Techniker hinzufügen
        if (!empty($data['technicians']) && is_array($data['technicians'])) {
            foreach ($data['technicians'] as $techData) {
                $tech = $technicianRepo->find($techData['id']);
                if ($tech) {
                    $appTech = new AppointmentTechnician();
                    $appTech->setTechnician($tech);
                    $appTech->setConfirmed($techData['confirmed'] ?? false);
                    $appointment->addAppointmentTechnician($appTech);
                }
            }
        }

        // Volunteers hinzufügen
        if (!empty($data['volunteers']) && is_array($data['volunteers'])) {
            foreach ($data['volunteers'] as $volData) {
                $vol = $volunteerRepo->find($volData['id']);
                if ($vol) {
                    $appVol = new AppointmentVolunteer();
                    $appVol->setVolunteer($vol);
                    $appVol->setConfirmed($volData['confirmed'] ?? false);
                    $appVol->setTasks($volData['tasks'] ?? []);
                    $appointment->addAppointmentVolunteer($appVol);
                }
            }
        }

        $em->persist($appointment);

        // Wiederholungen erstellen
        if (!empty($data['recurrenceFrequency']) && !empty($data['recurrenceEndDate'])) {
            try {
                $recurrenceEnd = new \DateTime($data['recurrenceEndDate']);
                $this->createRecurringAppointments(
                    $appointment,
                    $data['recurrenceFrequency'],
                    $recurrenceEnd,
                    $em,
                    $roomRepository,
                    $cleanRepo,
                    $productionRepo,
                    $technicianRepo,
                    $volunteerRepo,
                    $colorService
                );
            } catch (\Exception $e) {
                // Log error
            }
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'id' => $appointment->getId()
        ]);
    }

    private function createRecurringAppointments(
        Appointment $baseAppointment,
        string $frequency,
        \DateTime $endDate,
        EntityManagerInterface $em,
        RoomRepository $roomRepository,
        CleaningRepository $cleanRepo,
        ProductionRepository $productionRepo,
        TechnicianRepository $technicianRepo,
        VolunteerRepository $volunteerRepo,
        CalendarColorService $colorService
    ): void
    {
        $currentStart = clone $baseAppointment->getStartDate();
        $currentEnd = clone $baseAppointment->getEndDate();

        $interval = match($frequency) {
            'daily' => new \DateInterval('P1D'),
            'weekly' => new \DateInterval('P7D'),
            'monthly' => new \DateInterval('P1M'),
            default => null
        };

        if (!$interval) {
            return;
        }

        $baseAppointment->setRecurrenceFrequency($frequency);
        $baseAppointment->setRecurrenceEndDate($endDate);

        while (true) {
            $currentStart->add($interval);
            $currentEnd->add($interval);

            if ($currentStart > $endDate) {
                break;
            }

            $newAppointment = new Appointment();
            $newAppointment->setTitle($baseAppointment->getTitle());
            $newAppointment->setDescription($baseAppointment->getDescription());
            $newAppointment->setType($baseAppointment->getType());
            $newAppointment->setEventType($baseAppointment->getEventType());
            $newAppointment->setStatus($baseAppointment->getStatus());
            $newAppointment->setInternalTechniciansAttending($baseAppointment->isInternalTechniciansAttending());
            $newAppointment->setRoom($baseAppointment->getRoom());
            $newAppointment->setCleaning($baseAppointment->getCleaning());
            $newAppointment->setProduction($baseAppointment->getProduction());
            $newAppointment->setStartDate(clone $currentStart);
            $newAppointment->setEndDate(clone $currentEnd);
            $newAppointment->setAllDay($baseAppointment->isAllDay());
            $newAppointment->setColor($baseAppointment->getColor());
            $newAppointment->setParentAppointment($baseAppointment);
            $newAppointment->setRecurrenceFrequency($frequency);
            $newAppointment->setRecurrenceEndDate($endDate);

            // Techniker kopieren
            foreach ($baseAppointment->getAppointmentTechnicians() as $appTech) {
                $newAppTech = new AppointmentTechnician();
                $newAppTech->setTechnician($appTech->getTechnician());
                $newAppTech->setConfirmed($appTech->isConfirmed());
                $newAppointment->addAppointmentTechnician($newAppTech);
            }

            // Volunteers kopieren
            foreach ($baseAppointment->getAppointmentVolunteers() as $appVol) {
                $newAppVol = new AppointmentVolunteer();
                $newAppVol->setVolunteer($appVol->getVolunteer());
                $newAppVol->setConfirmed($appVol->isConfirmed());
                $newAppVol->setTasks($appVol->getTasks());
                $newAppointment->addAppointmentVolunteer($newAppVol);
            }

            $em->persist($newAppointment);
        }
    }

    #[Route('/appointment/{id}/edit', name: 'app_appointment_edit', methods: ['PUT'])]
    public function editAppointment(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $em,
        RoomRepository $roomRepository,
        CleaningRepository $cleanRepo,
        ProductionRepository $productionRepo,
        TechnicianRepository $technicianRepo,
        VolunteerRepository $volunteerRepo,
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

        // Type Update
        if (isset($data['type'])) {
            try {
                $appointment->setType(AppointmentTypeEnum::from($data['type']));
            } catch (\ValueError $e) {
                // Ignore
            }
        }

        // Event Type
        if (isset($data['eventType'])) {
            if ($data['eventType']) {
                try {
                    $appointment->setEventType(EventTypeEnum::from($data['eventType']));
                } catch (\ValueError $e) {
                    // Ignore
                }
            } else {
                $appointment->setEventType(null);
            }
        }

        // Status
        if (isset($data['status'])) {
            if ($data['status']) {
                try {
                    $appointment->setStatus(AppointmentStatusEnum::from($data['status']));
                } catch (\ValueError $e) {
                    // Ignore
                }
            } else {
                $appointment->setStatus(null);
            }
        }

        // Internal Technicians
        if (isset($data['internalTechniciansAttending'])) {
            $appointment->setInternalTechniciansAttending($data['internalTechniciansAttending']);
        }

        // Raum
        if (array_key_exists('roomId', $data)) {
            $appointment->setRoom(!empty($data['roomId']) ? $roomRepository->find($data['roomId']) : null);
        }

        // Relationen
        if (array_key_exists('cleaningId', $data)) {
            $appointment->setCleaning(!empty($data['cleaningId']) ? $cleanRepo->find($data['cleaningId']) : null);
        }
        if (array_key_exists('productionId', $data)) {
            $appointment->setProduction(!empty($data['productionId']) ? $productionRepo->find($data['productionId']) : null);
        }

        // Datum
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

        // Techniker aktualisieren
        if (isset($data['technicians']) && is_array($data['technicians'])) {
            // Alle vorhandenen entfernen
            foreach ($appointment->getAppointmentTechnicians() as $appTech) {
                $appointment->removeAppointmentTechnician($appTech);
            }

            // Neue hinzufügen
            foreach ($data['technicians'] as $techData) {
                $tech = $technicianRepo->find($techData['id']);
                if ($tech) {
                    $appTech = new AppointmentTechnician();
                    $appTech->setTechnician($tech);
                    $appTech->setConfirmed($techData['confirmed'] ?? false);
                    $appointment->addAppointmentTechnician($appTech);
                }
            }
        }

        // Volunteers aktualisieren
        if (isset($data['volunteers']) && is_array($data['volunteers'])) {
            // Alle vorhandenen entfernen
            foreach ($appointment->getAppointmentVolunteers() as $appVol) {
                $appointment->removeAppointmentVolunteer($appVol);
            }

            // Neue hinzufügen
            foreach ($data['volunteers'] as $volData) {
                $vol = $volunteerRepo->find($volData['id']);
                if ($vol) {
                    $appVol = new AppointmentVolunteer();
                    $appVol->setVolunteer($vol);
                    $appVol->setConfirmed($volData['confirmed'] ?? false);
                    $appVol->setTasks($volData['tasks'] ?? []);
                    $appointment->addAppointmentVolunteer($appVol);
                }
            }
        }

        // Farbe neu setzen
        $color = $this->getAppointmentColor($appointment, $colorService);
        $appointment->setColor($color);

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/appointment/{id}/delete', name: 'app_appointment_delete', methods: ['DELETE'])]
    public function deleteAppointment(
        Appointment $appointment,
        Request $request,
        EntityManagerInterface $em,
        AppointmentRepository $appointmentRepo
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $deleteMode = $data['mode'] ?? 'single'; // 'single' oder 'series'

        if ($deleteMode === 'series' && $appointment->getParentAppointment()) {
            // Lösche Parent und alle Kinder
            $parent = $appointment->getParentAppointment();
            $siblings = $appointmentRepo->findBy(['parentAppointment' => $parent]);

            foreach ($siblings as $sibling) {
                $em->remove($sibling);
            }
            $em->remove($parent);
        } elseif ($deleteMode === 'series' && $appointment->isRecurring()) {
            // Dieser Termin ist Parent, lösche alle Kinder
            $children = $appointmentRepo->findBy(['parentAppointment' => $appointment]);

            foreach ($children as $child) {
                $em->remove($child);
            }
            $em->remove($appointment);
        } else {
            // Nur diesen Termin löschen
            $em->remove($appointment);
        }

        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/appointments/all', name: 'app_appointments_all', methods: ['GET'])]
    public function getAllAppointments(
        AppointmentRepository $appointmentRepository,
        TranslatorInterface $translator
    ): JsonResponse
    {
        $appointments = $appointmentRepository->findAllForCalendar();

        $events = array_map(function (Appointment $appointment) use ($translator) {
            $endDate = clone $appointment->getEndDate();

            if ($appointment->isAllDay()) {
                $endDate->modify('+1 day')->setTime(0, 0, 0);
            }

            $appType = $appointment->getType() ?? AppointmentTypeEnum::PRIVATE;
            $typeFilter = $this->mapAppointmentTypeToFilter($appType);
            $displayTitle = $this->buildAppointmentTitle($appointment, $translator);

            return [
                'id' => $appointment->getId(),
                'title' => $displayTitle,
                'start' => $appointment->getStartDate()->format('Y-m-d\TH:i:s'),
                'end' => $endDate->format('Y-m-d\TH:i:s'),
                'allDay' => $appointment->isAllDay(),
                'color' => $appointment->getColor(),
                'description' => $appointment->getDescription(),
                'extendedProps' => [
                    'type' => $typeFilter,
                    'roomId' => $appointment->getRoom() ? $appointment->getRoom()->getId() : null,
                    'cleaningId' => $appointment->getCleaning() ? $appointment->getCleaning()->getId() : null,
                    'productionId' => $appointment->getProduction() ? $appointment->getProduction()->getId() : null,
                    'originalTitle' => $appointment->getTitle(),
                    'appointmentType' => $appType->value,
                    'eventType' => $appointment->getEventType()?->value,
                    'status' => $appointment->getStatus()?->value,
                    'internalTechniciansAttending' => $appointment->isInternalTechniciansAttending(),
                ]
            ];
        }, $appointments);

        return $this->json($events);
    }
}
