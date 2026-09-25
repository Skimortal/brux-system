<?php

namespace App\Controller;

use App\Entity\KeyManagement;
use App\Form\KeyType;
use App\Repository\KeyManagementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/schluessel')]
final class KeyController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_key_index', methods: ['GET'])]
    public function index(KeyManagementRepository $keys): Response
    {
        return $this->render('key/index.html.twig', ['keys' => $keys->findAllWithLoans(), 'now' => new \DateTimeImmutable()]);
    }

    #[Route('/neu', name: 'app_key_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        return $this->form(new KeyManagement(), $request, $em);
    }

    #[Route('/{id}/bearbeiten', name: 'app_key_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(KeyManagement $key, Request $request, EntityManagerInterface $em): Response
    {
        return $this->form($key, $request, $em);
    }

    #[Route('/{id}/loeschen', name: 'app_key_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(KeyManagement $key, Request $request, EntityManagerInterface $em): Response
    {
        return $this->deleteEntity($request, $em, $key, 'delete-key-' . $key->getId(), 'app_key_index', 'Schlüssel');
    }

    private function form(KeyManagement $key, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(KeyType::class, $key);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($key);
            $em->flush();
            $this->addFlash('success', 'Schlüssel gespeichert.');

            return $this->redirectToRoute('app_key_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $key->getId() ? $key->getName() : 'Neuer Schlüssel',
            'crumbs' => [['label' => 'Schlüssel', 'url' => $this->generateUrl('app_key_index')]],
            'intro' => 'Wer einen Schlüssel wann hat, trägst du direkt im Kalender ein.',
            'form' => $form,
            'back_url' => $this->generateUrl('app_key_index'),
            'delete_url' => $key->getId() && $this->isGranted('ROLE_ADMIN') ? $this->generateUrl('app_key_delete', ['id' => $key->getId()]) : null,
            'delete_token' => 'delete-key-' . $key->getId(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
