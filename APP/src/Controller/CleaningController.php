<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Cleaning;
use App\Entity\CleaningException;
use App\Form\CleaningType;
use App\Repository\AppointmentRepository;
use App\Repository\CleaningExceptionRepository;
use App\Repository\CleaningRepository;
use App\Repository\CleaningScheduleRepository;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/cleaning')]
#[IsGranted('ROLE_USER')]
class CleaningController extends AbstractController
{
    #[Route('/', name: 'app_cleaning_index', methods: ['GET'])]
    public function index(CleaningRepository $repository): Response
    {
        return $this->render('cleaning/index.html.twig', [
            'cleanings' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_cleaning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $cleaning = new Cleaning();
        $form = $this->createForm(CleaningType::class, $cleaning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($cleaning);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_cleaning_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('cleaning/detail.html.twig', [
            'cleaning' => $cleaning,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cleaning_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cleaning $cleaning, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(CleaningType::class, $cleaning);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($cleaning);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_cleaning_edit', ['id' => $cleaning->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('cleaning/detail.html.twig', [
            'cleaning' => $cleaning,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cleaning_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Cleaning $cleaning, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($cleaning);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_cleaning_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_cleaning_edit', ['id' => $cleaning->getId()]);
        }
    }

    #[Route('/exception/toggle', name: 'app_cleaning_exception_toggle', methods: ['POST'])]
    public function toggleCleaningException(
        Request                     $request,
        EntityManagerInterface      $em,
        ContactRepository           $contactRepository,
        CleaningExceptionRepository $exceptionRepo
    ): Response {
        $data = json_decode($request->getContent(), true);

        $cleaningId = $data['cleaningId'] ?? null;
        $date = $data['date'] ?? null;

        if (!$cleaningId || !$date) {
            return $this->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $cleaning = $contactRepository->find((int)$cleaningId);
        if (!$cleaning) {
            return $this->json(['success' => false, 'message' => 'Cleaning not found'], 404);
        }

        $existing = $exceptionRepo->findOneBy([
            'cleaningContact' => $cleaning,
            'date' => new \DateTime($date),
            'type' => CleaningException::TYPE_CANCEL
        ]);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            return $this->json(['success' => true, 'canceled' => false]);
        }

        $ex = new CleaningException();
        $ex->setCleaningContact($cleaning);
        $ex->setDate(new \DateTime($date));
        $ex->setType(CleaningException::TYPE_CANCEL);

        $em->persist($ex);
        $em->flush();

        return $this->json(['success' => true, 'canceled' => true]);
    }

    #[Route('/report/monthly', name: 'app_cleaning_report_monthly', methods: ['GET'])]
    public function monthlyReport(
        Request                     $request,
        ContactRepository           $contactRepository,
        CleaningExceptionRepository $exceptionRepo,
        CleaningScheduleRepository  $scheduleRepo
    ): Response {
        $cleaningId = $request->query->get('cleaningId');
        $month = $request->query->get('month'); // YYYY-MM

        if (!$cleaningId || !$month) {
            return new Response('Missing parameters', 400);
        }

        $contact = $contactRepository->find((int)$cleaningId);
        if (!$contact) {
            return new Response('Cleaning not found', 404);
        }

        $start = new \DateTime($month . '-01');
        $end = (clone $start)->modify('last day of this month');

        $exceptions = $exceptionRepo->createQueryBuilder('e')
            ->leftJoin('e.appointment', 'a')
            ->where('e.cleaningContact = :contact or a.cleaningContact = :contact')
            ->andWhere('e.date BETWEEN :start AND :end')
            ->setParameter('contact', $contact)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        $schedule = $scheduleRepo->findOneBy(['cleaningContact' => $contact]);

        $canceled = [];
        $extra = [];

        foreach ($exceptions as $ex) {
            if ($ex->getType() === \App\Entity\CleaningException::TYPE_CANCEL) {
                $timeFrom = null;
                $timeTo = null;

                if ($schedule) {
                    $timeFrom = $schedule->getTimeFrom();
                    $timeTo = $schedule->getTimeTo();
                }

                $canceled[] = [
                    'date' => $ex->getDate(),
                    'timeFrom' => $timeFrom,
                    'timeTo' => $timeTo,
                ];
            } else {
                $extra[] = $ex;
            }
        }

        $html = $this->renderView('cleaning/report_monthly.html.twig', [
            'contact' => $contact,
            'month' => $start,
            'canceled' => $canceled,
            'extra' => $extra
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contact-report.pdf"'
        ]);
    }

    #[Route('/exception/extra', name: 'app_cleaning_exception_extra', methods: ['POST'])]
    public function createExtraCleaning(
        Request $request,
        EntityManagerInterface $em,
        AppointmentRepository $appointmentRepository,
        CleaningExceptionRepository $exRepo
    ): Response {
        $data = json_decode($request->getContent(), true);

        $appointmentId = $data['appointmentId'] ?? null;
        $date = $data['date'] ?? null;
        $timeFrom = $data['timeFrom'] ?? null;
        $timeTo = $data['timeTo'] ?? null;

        if (!$appointmentId || !$date || !$timeFrom || !$timeTo) {
            return $this->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        /** @var Appointment $appointment */
        $appointment = $appointmentRepository->find((int)$appointmentId);
        if (!$appointment) {
            return $this->json(['success' => false, 'message' => 'Cleaning not found'], 404);
        }

        $ex = $exRepo->findOneBy(['appointment' => $appointment]);
        if(!$ex) $ex = new \App\Entity\CleaningException();

        $ex->setAppointment($appointment);
        $ex->setDate(new \DateTime($date));
        $ex->setType(\App\Entity\CleaningException::TYPE_EXTRA);
        $ex->setTimeFrom(new \DateTime($timeFrom));
        $ex->setTimeTo(new \DateTime($timeTo));

        $em->persist($ex);
        $em->flush();

        return $this->json(['success' => true]);
    }

}
