<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user, ['profile' => true, 'technician' => $user->isTechnician()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $form->get('plainPassword')->getData();
            if ($password) {
                $user->setPassword($hasher->hashPassword($user, $password));
            }
            $avatar = $form->get('avatarFile')->getData();
            if ($avatar !== null) {
                $name = $slugger->slug(pathinfo($avatar->getClientOriginalName(), \PATHINFO_FILENAME)) . '-' . uniqid() . '.' . ($avatar->guessExtension() ?: 'jpg');
                $avatar->move($this->getParameter('avatars_dir'), $name);
                $old = $user->getAvatarFilename();
                $user->setAvatarFilename($name);
                if ($old !== null && is_file($this->getParameter('avatars_dir') . '/' . basename($old))) {
                    @unlink($this->getParameter('avatars_dir') . '/' . basename($old));
                }
            }
            // a technician's contact in the office follows their own profile
            if (($contact = $user->getContact()) !== null) {
                $contact->setName(trim($user->getFirstName() . ' ' . $user->getLastName()));
                $contact->setEmail($user->getEmail());
                $phone = trim((string) ($user->getMobileNumber() ?: $user->getPhoneNumber()));
                if ($phone !== '') {
                    $contact->setPhone(mb_substr($phone, 0, 20));
                }
            }
            $em->flush();
            $this->addFlash('success', 'Profil gespeichert.');

            return $this->redirectToRoute('app_profile', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => 'Mein Profil',
            'crumbs' => [],
            'form' => $form,
            'back_url' => $this->generateUrl($user->isTechnician() ? 'app_technician' : 'app_calendar'),
            'intro' => $user->getContact() !== null ? 'Name, E-Mail und Telefon werden auch in deinen Kontakt beim Büro übernommen. Angemeldet wirst du weiterhin mit deinem Benutzernamen ' . $user->getUsername() . '.' : null,
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
