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

#[Route('/reinigungsplan')]
final class CleaningScheduleController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_cleaning_schedule_index', methods: ['GET'])]
    public function index(CleaningScheduleRepository $schedules): Response
    {
        return $this->render('cleaning/index.html.twig', ['schedules' => $schedules->findBy([], ['id' => 'ASC'])]);
    }

    #[Route('/neu', name: 'app_cleaning_schedule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        return $this->form(new CleaningSchedule(), $request, $em);
    }

    #[Route('/{id}', name: 'app_cleaning_schedule_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(CleaningSchedule $schedule, Request $request, EntityManagerInterface $em): Response
    {
        return $this->form($schedule, $request, $em);
    }

    #[Route('/{id}/loeschen', name: 'app_cleaning_schedule_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(CleaningSchedule $schedule, Request $request, EntityManagerInterface $em): Response
    {
        return $this->deleteEntity($request, $em, $schedule, 'delete-schedule-' . $schedule->getId(), 'app_cleaning_schedule_index', 'Reinigungsplan');
    }

    private function form(CleaningSchedule $schedule, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CleaningScheduleType::class, $schedule);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($schedule);
            $em->flush();
            $this->addFlash('success', 'Reinigungsplan gespeichert.');

            return $this->redirectToRoute('app_cleaning_schedule_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $schedule->getId() ? 'Reinigungsplan bearbeiten' : 'Neuer Reinigungsplan',
            'crumbs' => [['label' => 'Reinigungsplan', 'url' => $this->generateUrl('app_cleaning_schedule_index')]],
            'intro' => 'Regelmäßige Reinigung. Einzelne Tage sagst du direkt im Kalender ab; Extra-Reinigungen legst du dort als Termin an.',
            'form' => $form,
            'back_url' => $this->generateUrl('app_cleaning_schedule_index'),
            'delete_url' => $schedule->getId() && $this->isGranted('ROLE_ADMIN') ? $this->generateUrl('app_cleaning_schedule_delete', ['id' => $schedule->getId()]) : null,
            'delete_token' => 'delete-schedule-' . $schedule->getId(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
