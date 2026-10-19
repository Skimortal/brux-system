<?php

namespace App\Controller;

use App\Calendar\CalendarBuilder;
use App\Calendar\CalendarQuery;
use App\Calendar\EntryGroup;
use App\Entity\Appointment;
use App\Entity\AppointmentTechnician;
use App\Entity\Contact;
use App\Entity\User;
use App\Enum\ContactRole;
use App\Enum\TechnicianStatus;
use App\Repository\AppointmentRepository;
use App\Repository\AppointmentTechnicianRepository;
use App\Repository\ProductionEventRepository;
use App\Repository\ProductionRepository;
use App\Service\TechnicianNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The technicians' own area: all appointments read-only, their assignments marked and
 * listed, answers to assignments and interest in open appointments.
 */
final class TechnicianController extends AbstractController
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly EntityManagerInterface $em,
        private readonly AppointmentTechnicianRepository $assignments,
        private readonly AppointmentRepository $appointments,
    ) {
    }

    /** Landing page after login: technicians go to their area, everyone else to the calendar. */
    #[Route('/start', name: 'app_start', methods: ['GET'])]
    public function start(): Response
    {
        return $this->redirectToRoute($this->isGranted('ROLE_USER') ? 'app_calendar' : 'app_technician');
    }

    #[Route('/technik', name: 'app_technician', methods: ['GET'])]
    #[IsGranted('ROLE_TECHNICIAN')]
    public function index(Request $request, CalendarBuilder $builder, ProductionEventRepository $performances, ProductionRepository $productions): Response
    {
        $now = $this->clock->now();
        $query = CalendarQuery::fromRequest($request, $now->setTime(0, 0));
        // key loans name the people holding keys; they are not part of this area
        $query = new CalendarQuery($query->date, $query->view, $query->layout, array_values(array_unique([...$query->hidden, EntryGroup::KEYS], \SORT_REGULAR)), $query->panel, $query->side);
        $cal = $builder->build($query);
        $contact = $this->contact();

        // own assignments in the visible range, to mark them in the calendar
        $mine = [];
        if ($contact !== null) {
            foreach ($this->assignments->findForContact($contact, $query->rangeStart(), $query->rangeEnd()) as $assignment) {
                $mine[TechnicianNotifier::panelOf($assignment->getAppointment())] = $assignment->getStatus()->value;
            }
        }

        return $this->render('technician/index.html.twig', [
            'query' => $query,
            'cal' => $cal,
            'mine' => $mine,
            'contact' => $contact,
            'panel' => $query->panel !== null ? $this->panel((string) $query->panel, $performances, $productions) : null,
            'upcoming' => $contact !== null ? $this->assignments->findForContact($contact, $now) : [],
            'open_calls' => $this->appointments->findOpenCalls($now),
            'return_url' => $request->getRequestUri(),
            'now' => $now,
        ]);
    }

    /** Accept or decline an assignment from the technician area. */
    #[Route('/technik/einsatz/{id}/antwort', name: 'app_technician_respond', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TECHNICIAN')]
    public function respond(AppointmentTechnician $assignment, Request $request): Response
    {
        $this->requireToken($request);
        if ($assignment->getContact() === null || $assignment->getContact() !== $this->contact()) {
            throw $this->createAccessDeniedException('Das ist nicht deine Einteilung.');
        }
        $status = TechnicianStatus::tryFrom($request->request->getString('status'));
        if (!\in_array($status, [TechnicianStatus::ACCEPTED, TechnicianStatus::DECLINED], true)) {
            throw $this->createAccessDeniedException();
        }
        if ($assignment->getStatus() === TechnicianStatus::CONFIRMED && $status === TechnicianStatus::ACCEPTED) {
            $this->addFlash('success', 'Dein Einsatz ist schon bestätigt.');
        } else {
            $assignment->respond($status, $this->clock->now());
            $this->em->flush();
            $this->addFlash('success', $status === TechnicianStatus::ACCEPTED ? 'Danke! Deine Zusage ist beim Büro.' : 'Absage ist beim Büro.');
        }

        return $this->back($request);
    }

    /** Interest in an open appointment, from the technician area. */
    #[Route('/technik/termin/{id}/interesse', name: 'app_technician_interest_here', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TECHNICIAN')]
    public function interestHere(Appointment $appointment, Request $request): Response
    {
        $this->requireToken($request);
        $contact = $this->contact() ?? throw $this->createAccessDeniedException('Dein Zugang ist mit keinem Technik-Kontakt verknüpft.');
        $this->addFlash('success', $this->registerInterest($appointment, $contact));

        return $this->back($request);
    }

    /** Interest in an open appointment straight from the e-mail, without logging in. */
    #[Route('/technik/interesse/{id}/{contact}', name: 'app_technician_interest', methods: ['GET', 'POST'], requirements: ['id' => '\d+', 'contact' => '\d+'])]
    public function interestFromMail(Appointment $appointment, Contact $contact, Request $request, UriSigner $signer, TechnicianNotifier $notifier): Response
    {
        if (!$signer->checkRequest($request) || $contact->getCategory()?->getRole() !== ContactRole::TECHNICIAN) {
            throw new NotFoundHttpException('Der Link ist ungültig.');
        }
        // mail scanners open links; only the button on this page registers the interest
        $message = null;
        if ($request->isMethod('POST')) {
            $message = $this->registerInterest($appointment, $contact);
        }

        return $this->render('technician/interest.html.twig', [
            'appointment' => $notifier->describe($appointment, null),
            'name' => $contact->getName(),
            'message' => $message,
            'open' => $appointment->getOpenCallAt() !== null && $appointment->getEndDate() > $this->clock->now(),
            'assignment' => $appointment->getAssignmentOf($contact),
        ]);
    }

    private function registerInterest(Appointment $appointment, Contact $contact): string
    {
        if ($appointment->getOpenCallAt() === null || $appointment->getEndDate() <= $this->clock->now()) {
            return 'Für diesen Termin wird keine Technik mehr gesucht.';
        }
        $existing = $appointment->getAssignmentOf($contact);
        if ($existing !== null) {
            return $existing->getStatus() === TechnicianStatus::INTERESTED
                ? 'Dein Interesse ist schon gemeldet.'
                : 'Du bist für diesen Termin bereits ' . $existing->getStatus()->label() . '.';
        }
        $assignment = (new AppointmentTechnician())->setContact($contact)->respond(TechnicianStatus::INTERESTED, $this->clock->now());
        $appointment->addAppointmentTechnician($assignment);
        $this->em->persist($assignment);
        $this->em->flush();

        return 'Danke! Dein Interesse ist beim Büro, wir melden uns mit der Einteilung.';
    }

    /**
     * Read-only details of a calendar entry.
     *
     * @return array<string, mixed>
     */
    private function panel(string $panel, ProductionEventRepository $performances, ProductionRepository $productions): ?array
    {
        [$kind, $id] = array_pad(explode('-', $panel), 2, null);
        $appointment = null;
        $production = null;
        $performance = null;
        switch ($kind) {
            case 'pe':
                $performance = $performances->find((int) $id) ?? throw $this->createNotFoundException();
                $production = $performance->getProduction();
                $appointment = $performance->getAppointment();
                break;
            case 'a':
                $appointment = $this->appointments->find((int) $id) ?? throw $this->createNotFoundException();
                $production = $appointment->getProduction();
                break;
            case 'p':
                $production = $productions->find((int) $id) ?? throw $this->createNotFoundException();
                break;
            default:
                return null;
        }
        $contact = $this->contact();

        return [
            'appointment' => $appointment,
            'performance' => $performance,
            'production' => $production,
            'assignments' => $appointment !== null ? array_values(array_filter($appointment->getAppointmentTechnicians()->toArray(), static fn (AppointmentTechnician $t) => $t->getStatus() !== TechnicianStatus::DECLINED)) : [],
            'mine' => $appointment?->getAssignmentOf($contact),
            'past' => $appointment !== null && $appointment->getEndDate() <= $this->clock->now(),
        ];
    }

    private function contact(): ?Contact
    {
        $user = $this->getUser();

        return $user instanceof User ? $user->getContact() : null;
    }

    private function requireToken(Request $request): void
    {
        if (!$this->isCsrfTokenValid('technician', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Ungültiges Formular. Bitte Seite neu laden.');
        }
    }

    private function back(Request $request): Response
    {
        $return = (string) $request->request->get('_return', '');

        return $this->redirect(str_starts_with($return, '/technik') ? $return : $this->generateUrl('app_technician'), Response::HTTP_SEE_OTHER);
    }
}
