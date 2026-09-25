<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Entity\ProductionEvent;
use App\Form\AppointmentType;
use App\Form\PerformanceContactsType;
use App\Service\AppointmentManager;
use App\Service\WordPressPushService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vorstellungen')]
final class PerformanceController extends AbstractController
{
    use CalendarActionTrait;

    public function __construct(private readonly CalendarPage $page)
    {
    }

    #[Route('/{id}/ansprechpartner', name: 'app_performance_contacts', methods: ['POST'])]
    public function contacts(ProductionEvent $performance, Request $request, EntityManagerInterface $em, WordPressPushService $push): Response
    {
        $form = $this->createForm(PerformanceContactsType::class, $performance);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $push->pushProductionEvent($performance);
            $this->addFlash('success', 'Ansprechpartner gespeichert.');

            return $this->backToCalendar($request, $this->page);
        }

        return $this->page->render($this->page->queryFromReturn($request), $request, ['contacts' => $form], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/{id}/besetzung', name: 'app_performance_staffing', methods: ['POST'])]
    public function staffing(ProductionEvent $performance, Request $request, AppointmentManager $manager): Response
    {
        $staffing = $manager->staffingFor($performance);
        $form = $this->createForm(AppointmentType::class, $staffing, ['staffing' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->saveStaffing($performance, $staffing);
            $this->addFlash('success', 'Besetzung gespeichert.');

            return $this->backToCalendar($request, $this->page);
        }

        return $this->page->render($this->page->queryFromReturn($request), $request, ['staffing' => $form], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
