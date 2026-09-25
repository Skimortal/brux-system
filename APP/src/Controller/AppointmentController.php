<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Entity\Appointment;
use App\Form\AppointmentType;
use App\Service\AppointmentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/termine')]
final class AppointmentController extends AbstractController
{
    use CalendarActionTrait;

    public function __construct(
        private readonly CalendarPage $page,
        private readonly AppointmentManager $manager,
    ) {
    }

    #[Route('/neu', name: 'app_appointment_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $appointment = new Appointment();
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            [$frequency, $until] = $this->recurrence($form);
            $this->manager->create($appointment, $frequency, $until);
            $this->addFlash('success', $frequency ? 'Terminserie angelegt.' : 'Termin angelegt.');

            return $this->backToCalendar($request, $this->page, ['panel' => 'a-' . $appointment->getId()]);
        }

        $query = $this->page->queryFromReturn($request);

        return $this->page->render($query, $request, ['appointment' => $form], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/{id}', name: 'app_appointment_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Appointment $appointment, Request $request): Response
    {
        $form = $this->createForm(AppointmentType::class, $appointment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $scope = $form->has('scope') ? (string) $form->get('scope')->getData() : 'single';
            [$frequency, $until] = $this->recurrence($form);
            $this->manager->update($appointment, $scope, $frequency, $until);
            $this->addFlash('success', $scope === 'series' ? 'Alle Termine der Serie gespeichert.' : 'Termin gespeichert.');

            return $this->backToCalendar($request, $this->page, ['panel' => 'a-' . $appointment->getId()]);
        }

        return $this->page->render($this->page->queryFromReturn($request), $request, ['appointment' => $form], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/{id}/duplizieren', name: 'app_appointment_duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(Appointment $appointment, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $this->requireCsrf($request, $csrf, 'appointment-' . $appointment->getId());
        $copy = $this->manager->duplicate($appointment);
        $this->addFlash('success', 'Kopie angelegt – bitte Datum anpassen.');

        return $this->backToCalendar($request, $this->page, ['panel' => 'a-' . $copy->getId()]);
    }

    #[Route('/{id}/loeschen', name: 'app_appointment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Appointment $appointment, Request $request, CsrfTokenManagerInterface $csrf): Response
    {
        $this->requireCsrf($request, $csrf, 'appointment-' . $appointment->getId());
        $scope = $request->request->getString('scope', 'single') === 'series' ? 'series' : 'single';
        $this->manager->delete($appointment, $scope);
        $this->addFlash('success', $scope === 'series' ? 'Serie gelöscht.' : 'Termin gelöscht.');

        return $this->backToCalendar($request, $this->page, ['panel' => null]);
    }

    /**
     * @return array{0: ?string, 1: ?\DateTimeInterface}
     */
    private function recurrence(\Symfony\Component\Form\FormInterface $form): array
    {
        if (!$form->has('repeat')) {
            return [null, null];
        }
        $frequency = $form->get('repeat')->getData();
        $until = $form->get('repeatUntil')->getData();

        return $frequency && $until ? [(string) $frequency, $until] : [null, null];
    }
}
