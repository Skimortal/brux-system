<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Entity\CleaningSchedule;
use App\Repository\ContactRepository;
use App\Service\CleaningService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/reinigung')]
final class CleaningController extends AbstractController
{
    use CalendarActionTrait;

    #[Route('/{id}/{date}/absage', name: 'app_cleaning_cancel', methods: ['POST'], requirements: ['id' => '\d+', 'date' => '\d{8}'])]
    public function toggleCancel(CleaningSchedule $schedule, string $date, Request $request, CleaningService $cleaning, CalendarPage $page, CsrfTokenManagerInterface $csrf): Response
    {
        $this->requireCsrf($request, $csrf, 'cleaning-' . $schedule->getId() . '-' . $date);
        $day = \DateTimeImmutable::createFromFormat('!Ymd', $date) ?: throw new NotFoundHttpException();
        $cancel = $request->request->getBoolean('cancel');
        $cleaning->setCancelled($schedule, $day, $cancel);
        $this->addFlash('success', $cancel ? 'Reinigung für diesen Tag abgesagt.' : 'Absage zurückgenommen.');

        return $this->backToCalendar($request, $page);
    }

    #[Route('/bericht', name: 'app_cleaning_report', methods: ['GET'])]
    public function report(Request $request, CleaningService $cleaning, ContactRepository $contacts): Response
    {
        $contact = $contacts->find($request->query->getInt('contact'))
            ?? throw new NotFoundHttpException('Reinigungskraft nicht gefunden.');
        $month = \DateTimeImmutable::createFromFormat('!Y-m', $request->query->getString('month'))
            ?: new \DateTimeImmutable('first day of this month');

        $html = $this->renderView('cleaning/report.pdf.twig', [
            'contact' => $contact,
            'month' => $month,
            'report' => $cleaning->monthlyReport($contact, $month),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        $filename = sprintf('reinigung-%s-%s.pdf', $month->format('Y-m'), preg_replace('/[^a-z0-9]+/i', '-', (string) $contact->getName()));

        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
