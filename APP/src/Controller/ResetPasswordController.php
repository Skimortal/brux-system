<?php

namespace App\Controller;

use App\Enum\EmailTemplateKey;
use App\Form\ForgotPasswordRequestType;
use App\Form\Model\ForgotPasswordRequest;
use App\Form\Model\ResetPasswordData;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\MailTemplateRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgot(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        MailTemplateRenderer $templates,
        LoggerInterface $logger,
    ): Response {
        $model = new ForgotPasswordRequest();
        $form = $this->createForm(ForgotPasswordRequestType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $users->findOneBy(['email' => $model->email]);
            if ($user !== null && $user->isActive()) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token)->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $em->flush();
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                try {
                    $rendered = $templates->render(EmailTemplateKey::PASSWORD_RESET_REQUEST, $request->getLocale(), [
                        'user' => $user,
                        'resetUrl' => $resetUrl,
                        'expiresAt' => $user->getResetTokenExpiresAt(),
                    ]);
                    $email = (new Email())
                        ->from(new Address('no-reply@brux.at', 'brux Haussystem'))
                        ->to((string) $user->getEmail())
                        ->subject($rendered->subject)
                        ->html($rendered->html);
                    if ($rendered->text) {
                        $email->text($rendered->text);
                    }
                    $mailer->send($email);
                } catch (\Throwable $e) {
                    $logger->error('Password reset mail failed', ['exception' => $e]);
                }
            }
            $this->addFlash('success', 'Wenn die Adresse bekannt ist, wurde ein Link verschickt.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', ['form' => $form]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, UserRepository $users, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = $users->findOneBy(['resetToken' => $token]);
        if ($user === null || !$user->isResetTokenValid($token)) {
            $this->addFlash('danger', 'Der Link ist ungültig oder abgelaufen.');

            return $this->redirectToRoute('app_forgot_password');
        }

        $model = new ResetPasswordData();
        $form = $this->createForm(ResetPasswordType::class, $model);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, (string) $model->plainPassword));
            $user->setResetToken(null)->setResetTokenExpiresAt(null);
            $em->flush();
            $this->addFlash('success', 'Passwort geändert. Du kannst dich jetzt anmelden.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', ['form' => $form]);
    }
}
