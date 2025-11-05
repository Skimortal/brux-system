<?php

namespace App\Controller;

use App\Entity\Volunteer;
use App\Form\VolunteerType;
use App\Repository\VolunteerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/volunteer')]
#[IsGranted('ROLE_USER')]
class VolunteerController extends AbstractController
{
    #[Route('/', name: 'app_volunteer_index', methods: ['GET'])]
    public function index(VolunteerRepository $repository): Response
    {
        return $this->render('volunteer/index.html.twig', [
            'volunteers' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_volunteer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $volunteer = new Volunteer();
        $form = $this->createForm(VolunteerType::class, $volunteer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($volunteer);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_volunteer_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('volunteer/detail.html.twig', [
            'volunteer' => $volunteer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_volunteer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Volunteer $volunteer, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(VolunteerType::class, $volunteer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($volunteer);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_volunteer_edit', ['id' => $volunteer->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('volunteer/detail.html.twig', [
            'volunteer' => $volunteer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_volunteer_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Volunteer $volunteer, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($volunteer);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_volunteer_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_volunteer_edit', ['id' => $volunteer->getId()]);
        }
    }
}
