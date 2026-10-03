<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Entity\ProductionEvent;
use App\Form\AppointmentType;
use App\Form\PerformanceContactsType;
use App\Form\PerformanceType;
use App\Repository\RoomRepository;
use App\Service\AppointmentManager;
use App\Service\FieldOwnership;
use App\Service\WordPressPushService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/vorstellungen')]
final class PerformanceController extends AbstractController
{
    use CalendarActionTrait;

    public function __construct(private readonly CalendarPage $page)
    {
    }

    #[Route('/neu', name: 'app_performance_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, FieldOwnership $ownership, WordPressPushService $push): Response
    {
        $performance = new ProductionEvent();
        $form = $this->createForm(PerformanceType::class, $performance);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $production = $performance->getProduction();
            if ($production->isFromWordPress() && !$ownership->canCreatePerformances()) {
                throw $this->createAccessDeniedException('Vorstellungen dieser Produktion werden in WordPress angelegt.');
            }
            $performance->alignPricesWithProduction();
            $em->persist($performance);
            $em->flush();
            $result = $push->pushProductionEvent($performance);
            if ($result['status'] === 'error') {
                $this->addFlash('warning', 'Angelegt, aber die Übertragung an die Website ist fehlgeschlagen: ' . ($result['message'] ?? ''));
            } else {
                $this->addFlash('success', 'Vorstellung angelegt.');
            }

            return $this->backToCalendar($request, $this->page, ['panel' => 'pe-' . $performance->getId(), 'date' => $performance->getDate()?->format('Y-m-d')]);
        }

        return $this->page->render($this->page->queryFromReturn($request), $request, ['performance' => $form], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/{id}', name: 'app_performance_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(ProductionEvent $performance, Request $request, EntityManagerInterface $em, AppointmentManager $manager, WordPressPushService $push): Response
    {
        $form = $this->createForm(PerformanceType::class, $performance);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $performance->alignPricesWithProduction();
            if (($staffing = $performance->getAppointment()) !== null) {
                $manager->alignWithPerformance($staffing, $performance);
            }
            $em->flush();
            $result = $push->pushProductionEvent($performance);
            if ($result['status'] === 'error') {
                $this->addFlash('warning', 'Gespeichert, aber die Übertragung an die Website ist fehlgeschlagen: ' . ($result['message'] ?? ''));
            } else {
                $this->addFlash('success', 'Vorstellung gespeichert.');
            }

            return $this->backToCalendar($request, $this->page, ['panel' => 'pe-' . $performance->getId(), 'date' => $performance->getDate()?->format('Y-m-d')]);
        }

        // the form keeps the submitted values; header and calendar show the stored ones
        $em->refresh($performance);

        return $this->page->render($this->page->queryFromReturn($request), $request, ['performance' => $form], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/{id}/loeschen', name: 'app_performance_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(ProductionEvent $performance, Request $request, EntityManagerInterface $em, FieldOwnership $ownership, WordPressPushService $push): Response
    {
        if (!$this->isCsrfTokenValid('delete-performance-' . $performance->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        if ($ownership->isLocked($performance, 'date')) {
            throw $this->createAccessDeniedException('Vorstellungen dieser Produktion werden in WordPress gelöscht.');
        }
        // otherwise the next import would bring it back
        $result = $push->deleteProductionEvent($performance);
        if ($result['status'] === 'error') {
            $this->addFlash('danger', 'Nicht gelöscht, die Website hat abgelehnt: ' . ($result['message'] ?? ''));

            return $this->backToCalendar($request, $this->page);
        }
        if (($staffing = $performance->getAppointment()) !== null) {
            $em->remove($staffing);
        }
        $em->remove($performance);
        $em->flush();
        $this->addFlash('success', 'Vorstellung gelöscht.');

        return $this->backToCalendar($request, $this->page, ['panel' => null]);
    }

    /** Dropped on another day (and room) in the calendar; the start time stays. */
    #[Route('/{id}/verschieben', name: 'app_performance_move', methods: ['POST'])]
    public function move(ProductionEvent $performance, Request $request, EntityManagerInterface $em, FieldOwnership $ownership, AppointmentManager $manager, WordPressPushService $push, CsrfTokenManagerInterface $csrf, RoomRepository $rooms): Response
    {
        $this->requireCsrf($request, $csrf, 'move-performance');
        if ($ownership->isLocked($performance, 'date')) {
            throw $this->createAccessDeniedException('Vorstellungen dieser Produktion werden in WordPress verschoben.');
        }
        $date = \DateTime::createFromFormat('!Y-m-d', $request->request->getString('date'));
        if ($date === false) {
            throw $this->createNotFoundException('Ungültiges Datum.');
        }

        $performance->setDate($date);
        if (($roomId = $request->request->getInt('room')) > 0 && ($room = $rooms->find($roomId)) !== null) {
            $performance->setRoom($room);
        }
        if (($staffing = $performance->getAppointment()) !== null) {
            $manager->alignWithPerformance($staffing, $performance);
        }
        $em->flush();

        $label = sprintf('Vorstellung auf %s%s verschoben', $date->format('d.m.Y'), $performance->getRoom() ? ' · ' . $performance->getRoom()->getName() : '');
        $result = $push->pushProductionEvent($performance);
        if ($result['status'] === 'error') {
            $this->addFlash('warning', $label . ', aber die Übertragung an die Website ist fehlgeschlagen: ' . ($result['message'] ?? ''));
        } else {
            $this->addFlash('success', $label . '.');
        }

        return $this->backToCalendar($request, $this->page, ['panel' => 'pe-' . $performance->getId()]);
    }

    /** Lazily loaded into the performance panel. */
    #[Route('/{id}/reservierungen', name: 'app_performance_reservations', methods: ['GET'])]
    public function reservations(ProductionEvent $performance, WordPressPushService $push): Response
    {
        $prices = [];
        foreach ($performance->getPriceList() as $price) {
            $prices[$price->getPriceIndex()] = $price->getCategoryLabel();
        }

        // WordPress stores one row per price; one booking covers all rows of a submission
        $result = $push->fetchReservations($performance);
        $bookings = [];
        foreach ($result['reservations'] ?? [] as $row) {
            $key = implode('|', [$row['created_at_utc'] ?? $row['created_at_eu'] ?? '', mb_strtolower((string) ($row['email'] ?? '')), $row['last_name'] ?? '']);
            $bookings[$key] ??= $row + ['tickets' => [], 'seats' => 0];
            $bookings[$key]['tickets'][] = ($row['qty'] ?? 0) . ' × ' . ($prices[$row['price_index'] ?? null] ?? 'Preis ' . ($row['price_index'] ?? '?'));
            $bookings[$key]['seats'] += (int) ($row['qty'] ?? 0);
        }

        return $this->render('calendar/panel/_reservations.html.twig', [
            'performance' => $performance,
            'result' => $result,
            'bookings' => array_values($bookings),
        ]);
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

    /** Price rows are addressed by their position, as in WordPress. */}
