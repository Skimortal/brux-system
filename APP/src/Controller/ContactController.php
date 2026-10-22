<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Enum\ContactRole;
use App\Form\ContactType;
use App\Repository\UserRepository;
use App\Service\TechnicianNotifier;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\ContactCategoryRepository;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/kontakte')]
final class ContactController extends AbstractController
{
    use CrudTrait;

    public function __construct(private readonly UserRepository $users)
    {
    }

    #[Route('', name: 'app_contact_index', methods: ['GET'])]
    public function index(Request $request, ContactRepository $contacts, ContactCategoryRepository $categories): Response
    {
        $category = $request->query->getInt('kategorie');
        $term = trim($request->query->getString('q'));
        $list = $contacts->search($term, $category > 0 ? [$category] : []);
        $grouped = [];
        foreach ($list as $contact) {
            $grouped[$contact->getCategory()?->getName() ?? 'Ohne Kategorie'][] = $contact;
        }

        return $this->render('contact/index.html.twig', [
            'grouped' => $grouped,
            'categories' => $categories->findBy([], ['name' => 'ASC']),
            'category' => $category,
            'term' => $term,
            'count' => \count($list),
        ]);
    }

    #[Route('/neu', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ContactCategoryRepository $categories): Response
    {
        $contact = new Contact();
        if ($request->query->getInt('kategorie') > 0) {
            $contact->setCategory($categories->find($request->query->getInt('kategorie')));
        }

        return $this->form($contact, $request, $em);
    }

    #[Route('/{id}', name: 'app_contact_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        return $this->form($contact, $request, $em);
    }

    #[Route('/{id}/loeschen', name: 'app_contact_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        return $this->deleteEntity($request, $em, $contact, 'delete-contact-' . $contact->getId(), 'app_contact_index', 'Kontakt');
    }

    /**
     * Gives a technician their own read-only access: creates the account (or links an
     * existing one with the same address) and mails a link to set the password.
     */
    #[Route('/{id}/zugang', name: 'app_contact_invite', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function invite(Contact $contact, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, TechnicianNotifier $notifier, ClockInterface $clock): Response
    {
        if (!$this->isCsrfTokenValid('invite-contact-' . $contact->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Ungültiges Formular. Bitte Seite neu laden.');
        }
        $email = mb_strtolower(trim((string) $contact->getEmail()));
        // the contact's category decides which read-only area the account gets
        $role = match ($contact->getCategory()?->getRole()) {
            ContactRole::TECHNICIAN => 'ROLE_TECHNICIAN',
            ContactRole::BOARD => 'ROLE_BOARD',
            default => null,
        };
        if ($role === null || $email === '') {
            $this->addFlash('danger', 'Einen Zugang gibt es nur für Kontakte in der Technik oder im Vorstand mit E-Mail-Adresse.');

            return $this->redirectToRoute('app_contact_edit', ['id' => $contact->getId()], 303);
        }

        $user = $this->users->findOneBy(['contact' => $contact]) ?? $this->users->findOneBy(['email' => $email]);
        if ($user !== null && !$user->isReadOnly()) {
            // a staff account with the same address: it is simply linked to the contact
            $user->setContact($contact);
            $em->flush();
            $this->addFlash('success', 'Das bestehende Konto ' . $user->getUsername() . ' ist jetzt mit diesem Kontakt verknüpft.');

            return $this->redirectToRoute('app_contact_edit', ['id' => $contact->getId()], 303);
        }
        if ($user === null) {
            $parts = preg_split('/\s+/', trim((string) $contact->getName()), 2) ?: [''];
            $user = new User();
            $user->setUsername($email);
            $user->setEmail($email);
            $user->setFirstName($parts[0] !== '' ? $parts[0] : $email);
            $user->setLastName($parts[1] ?? '');
            $user->setRoles([$role]);
            $user->setIsActive(true);
            // nobody knows this password; the person sets their own through the link
            $user->setPassword($hasher->hashPassword($user, bin2hex(random_bytes(24))));
            $em->persist($user);
        }
        $user->setContact($contact);
        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token)->setResetTokenExpiresAt($clock->now()->modify('+7 days'));
        $em->flush();

        $sent = $notifier->invite($user, $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL), $role === 'ROLE_BOARD');
        $this->addFlash($sent ? 'success' : 'danger', $sent
            ? 'Einladung an ' . $email . ' verschickt. Benutzername: ' . $user->getUsername()
            : 'Konto angelegt, aber die Einladung konnte nicht verschickt werden.');

        return $this->redirectToRoute('app_contact_edit', ['id' => $contact->getId()], 303);
    }

    private function form(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($contact);
            $em->flush();
            $this->addFlash('success', 'Kontakt gespeichert.');

            return $this->afterSave($request, 'app_contact_index', ['kategorie' => $contact->getCategory()?->getId()], 'app_contact_edit', $contact->getId());
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $contact->getId() ? $contact->getName() : 'Neuer Kontakt',
            'crumbs' => [['label' => 'Kontakte', 'url' => $this->generateUrl('app_contact_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_contact_index'),
            'delete_url' => $contact->getId() && $this->isGranted('ROLE_ADMIN') ? $this->generateUrl('app_contact_delete', ['id' => $contact->getId()]) : null,
            'delete_token' => 'delete-contact-' . $contact->getId(),
            'extra' => $contact->getId() && \in_array($contact->getCategory()?->getRole(), [ContactRole::TECHNICIAN, ContactRole::BOARD], true)
                ? ['template' => 'contact/_access.html.twig', 'vars' => ['contact' => $contact, 'account' => $this->users->findOneBy(['contact' => $contact])]]
                : null,
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
