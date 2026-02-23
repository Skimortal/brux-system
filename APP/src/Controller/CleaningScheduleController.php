<?php

namespace App\Controller;

use App\Entity\CleaningSchedule;
use App\Form\CleaningScheduleType;
use App\Repository\CleaningScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/cleaning-schedule')]
#[IsGranted('ROLE_USER')]
class CleaningScheduleController extends AbstractController
{
    #[Route('/', name: 'app_cleaning_schedule_index', methods: ['GET'])]
    public function index(CleaningScheduleRepository $repository): Response
    {
        return $this->render('cleaning_schedule/index.html.twig', [
            'schedules' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_cleaning_schedule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, TranslatorInterface $t): Response
    {
        $schedule = new CleaningSchedule();
        $form = $this->createForm(CleaningScheduleType::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($schedule);
                $em->flush();
                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_cleaning_schedule_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('cleaning_schedule/detail.html.twig', [
            'schedule' => $schedule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_cleaning_schedule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CleaningSchedule $schedule, EntityManagerInterface $em, TranslatorInterface $t): Response
    {
        $form = $this->createForm(CleaningScheduleType::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->persist($schedule);
                $em->flush();
                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_cleaning_schedule_edit', ['id' => $schedule->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('cleaning_schedule/detail.html.twig', [
            'schedule' => $schedule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_cleaning_schedule_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(CleaningSchedule $schedule, EntityManagerInterface $em, TranslatorInterface $t): Response
    {
        try {
            $em->remove($schedule);
            $em->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_cleaning_schedule_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error'));
            return $this->redirectToRoute('app_cleaning_schedule_edit', ['id' => $schedule->getId()]);
        }
    }
}
