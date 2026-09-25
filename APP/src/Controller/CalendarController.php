<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Calendar\CalendarQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalendarController extends AbstractController
{
    #[Route('/', name: 'app_calendar', methods: ['GET'])]
    public function index(Request $request, CalendarPage $page, ClockInterface $clock): Response
    {
        return $page->render(CalendarQuery::fromRequest($request, $clock->now()->setTime(0, 0)), $request);
    }

    #[Route('/dashboard', name: 'app_dashboard_legacy', methods: ['GET'])]
    public function legacyDashboard(): RedirectResponse
    {
        return $this->redirectToRoute('app_calendar', [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
