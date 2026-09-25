<?php

namespace App\Controller\Admin;

use App\Controller\CrudTrait;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/verwaltung/benutzer')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $users): Response
    {
        return $this->render('crud/list.html.twig', [
            'title' => 'Benutzer:innen',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')]],
            'new_url' => $this->generateUrl('app_user_new'),
            'columns' => ['Name', 'Benutzername', 'E-Mail', 'Rechte', 'Status'],
            'rows' => array_map(fn (User $u) => [
                'url' => $this->generateUrl('app_user_edit', ['id' => $u->getId()]),
                'cells' => [$u->getFullname(), $u->getUsername(), $u->getEmail(), self::roleLabel($u), $u->isActive() ? 'aktiv' : 'deaktiviert'],
            ], $users->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC'])),
        ]);
    }

    #[Route('/neu', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        return $this->form(new User(), $request, $em, $hasher);
    }

    #[Route('/{id}', name: 'app_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        if (\in_array('ROLE_SUPERUSER', $user->getAssignedRoles(), true) && !$this->isGranted('ROLE_SUPERUSER')) {
            $this->addFlash('danger', 'Superadmins können nur von Superadmins bearbeitet werden.');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->form($user, $request, $em, $hasher);
    }

    #[Route('/{id}/loeschen', name: 'app_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('danger', 'Du kannst dich nicht selbst löschen.');

            return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()], 303);
        }

        return $this->deleteEntity($request, $em, $user, 'delete-user-' . $user->getId(), 'app_user_index', 'Benutzer:in');
    }

    private function form(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $isNew = $user->getId() === null;
        $form = $this->createForm(UserType::class, $user, [
            'password_required' => $isNew,
            'can_grant_superuser' => $this->isGranted('ROLE_SUPERUSER'),
            'current_role' => self::highestRole($user),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $role = (string) $form->get('role')->getData();
            $user->setRoles($role === 'ROLE_USER' ? [] : [$role]);
            $password = $form->get('plainPassword')->getData();
            if ($password) {
                $user->setPassword($hasher->hashPassword($user, $password));
            }
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Benutzer:in gespeichert.');

            return $this->redirectToRoute('app_user_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $isNew ? 'Neue:r Benutzer:in' : $user->getFullname(),
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')], ['label' => 'Benutzer:innen', 'url' => $this->generateUrl('app_user_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_user_index'),
            'delete_url' => !$isNew && $user !== $this->getUser() ? $this->generateUrl('app_user_delete', ['id' => $user->getId()]) : null,
            'delete_token' => 'delete-user-' . $user->getId(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }

    private static function highestRole(User $user): string
    {
        $roles = $user->getAssignedRoles();
        foreach (['ROLE_SUPERUSER', 'ROLE_ADMIN'] as $role) {
            if (\in_array($role, $roles, true)) {
                return $role;
            }
        }

        return 'ROLE_USER';
    }

    private static function roleLabel(User $user): string
    {
        return match (self::highestRole($user)) {
            'ROLE_SUPERUSER' => 'Superadmin',
            'ROLE_ADMIN' => 'Verwaltung',
            default => 'Mitarbeit',
        };
    }
}
