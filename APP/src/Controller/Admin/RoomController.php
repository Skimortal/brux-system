<?php

namespace App\Controller\Admin;

use App\Controller\CrudTrait;
use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/verwaltung/raeume')]
#[IsGranted('ROLE_ADMIN')]
final class RoomController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_room_index', methods: ['GET'])]
    public function index(RoomRepository $rooms): Response
    {
        return $this->render('crud/list.html.twig', [
            'title' => 'Räume',
            'subtitle' => 'Reihenfolge und Sichtbarkeit der Raum-Zeilen im Kalender.',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')]],
            'new_url' => $this->generateUrl('app_room_new'),
            'columns' => ['Name', 'Kürzel WordPress', 'Reihenfolge', 'Immer im Kalender'],
            'rows' => array_map(fn (Room $r) => [
                'url' => $this->generateUrl('app_room_edit', ['id' => $r->getId()]),
                'cells' => [$r->getName(), $r->getExternalId() ?? '–', $r->getPosition(), $r->isShowOnDashboard() ? 'ja' : 'nein'],
            ], $rooms->findBy([], ['position' => 'ASC', 'name' => 'ASC'])),
        ]);
    }

    #[Route('/neu', name: 'app_room_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        return $this->form(new Room(), $request, $em);
    }

    #[Route('/{id}', name: 'app_room_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Room $room, Request $request, EntityManagerInterface $em): Response
    {
        return $this->form($room, $request, $em);
    }

    #[Route('/{id}/loeschen', name: 'app_room_delete', methods: ['POST'])]
    public function delete(Room $room, Request $request, EntityManagerInterface $em): Response
    {
        return $this->deleteEntity($request, $em, $room, 'delete-room-' . $room->getId(), 'app_room_index', 'Raum');
    }

    private function form(Room $room, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($room);
            $em->flush();
            $this->addFlash('success', 'Raum gespeichert.');

            return $this->redirectToRoute('app_room_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $room->getId() ? $room->getName() : 'Neuer Raum',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')], ['label' => 'Räume', 'url' => $this->generateUrl('app_room_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_room_index'),
            'delete_url' => $room->getId() ? $this->generateUrl('app_room_delete', ['id' => $room->getId()]) : null,
            'delete_token' => 'delete-room-' . $room->getId(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
