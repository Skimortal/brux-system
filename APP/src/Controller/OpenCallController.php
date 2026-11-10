<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Entity\Appointment;
use App\Entity\ProductionEvent;
use App\Service\AppointmentManager;
use App\Service\OpenCall;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Sends an appointment (or the staffing of a performance) out to all technicians as open,
 * so they can show interest with one click.
 */
final class OpenCallController extends AbstractController
{
    use CalendarActionTrait;

    public function __construct(
        private readonly CalendarPage $page,
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly OpenCall $openCall,
    ) {
    }

    #[Route('/termine/{id}/ausschreiben', name: 'app_open_call', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function open(Appointment $appointment, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'open-call');

        return $this->sendOut($appointment, $request);
    }

    #[Route('/vorstellungen/{id}/ausschreiben', name: 'app_open_call_performance', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function openPerformance(ProductionEvent $performance, Request $request, AppointmentManager $manager): Response
    {
        $this->requireCsrf($request, $this->csrf, 'open-call');
        $staffing = $manager->staffingFor($performance);
        if ($staffing->getId() === null) {
            $manager->saveStaffing($performance, $staffing);
        }

        return $this->sendOut($staffing, $request);
    }

    #[Route('/termine/{id}/ausschreibung-beenden', name: 'app_open_call_close', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function close(Appointment $appointment, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'open-call');
        $appointment->setOpenCallAt(null);
        $this->em->flush();
        $this->addFlash('success', 'Ausschreibung beendet.');

        return $this->backToCalendar($request, $this->page);
    }

    #[Route('/vorstellungen/{id}/ausschreibung-beenden', name: 'app_open_call_performance_close', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function closePerformance(ProductionEvent $performance, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'open-call');
        $performance->getAppointment()?->setOpenCallAt(null);
        $this->em->flush();
        $this->addFlash('success', 'Ausschreibung beendet.');

        return $this->backToCalendar($request, $this->page);
    }

    private function sendOut(Appointment $appointment, Request $request): Response
    {
        $this->addFlash(...$this->openCall->message($appointment, $this->openCall->send($appointment)));

        return $this->backToCalendar($request, $this->page);
    }
}
