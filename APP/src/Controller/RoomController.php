<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/room')]
#[IsGranted('ROLE_USER')]
class RoomController extends AbstractController
{
    #[Route('/', name: 'app_room_index', methods: ['GET'])]
    public function index(RoomRepository $repository): Response
    {
        return $this->render('room/index.html.twig', [
            'rooms' => $repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_room_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $room = new Room();
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($room);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('room/detail.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_room_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Room $room, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($room);
                $entityManager->flush();

                $this->addFlash('success', $t->trans('data_saved_success'));
                return $this->redirectToRoute('app_room_edit', ['id' => $room->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $t->trans('data_save_error'));
            }
        }

        return $this->render('room/detail.html.twig', [
            'room' => $room,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_room_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Room $room, EntityManagerInterface $entityManager, TranslatorInterface $t): Response
    {
        try {
            $entityManager->remove($room);
            $entityManager->flush();
            $this->addFlash('warning', $t->trans('data_deleted_success'));
            return $this->redirectToRoute('app_room_index', [], Response::HTTP_SEE_OTHER);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $t->trans('data_save_error').": ".$e->getMessage());
            return $this->redirectToRoute('app_room_edit', ['id' => $room->getId()]);
        }
    }
}
