<?php
// src/Controller/ResetPasswordController.php
namespace App\Controller;

use App\Entity\User;
use App\Enum\EmailTemplateKey;
use App\Form\ForgotPasswordRequestType;
use App\Form\Model\ForgotPasswordRequest;
use App\Form\Model\ResetPasswordData;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use App\Service\MailTemplateRenderer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password', methods: ['GET','POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        TranslatorInterface $t,
        MailTemplateRenderer $templates,
        LoggerInterface $logger
    ): Response {
        $model = new ForgotPasswordRequest();
        $form = $this->createForm(ForgotPasswordRequestType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User|null $user */
            $user = $users->findOneBy(['email' => $model->email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt((new \DateTimeImmutable('+1 hour')));
                $em->flush();

                $resetUrl = $this->generateUrl(
                    'reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                // bevorzugte Locale (User > Request)
                $locale = method_exists($user, 'getLocale') && $user->getLocale()
                    ? $user->getLocale()
                    : $request->getLocale();

                try {
                    // DB-Template "password_reset_request" rendern
                    $rendered = $templates->render(EmailTemplateKey::PASSWORD_RESET_REQUEST, $locale, [
                        'user'      => $user,
                        'resetUrl'  => $resetUrl,
                        'expiresAt' => $user->getResetTokenExpiresAt(),
                    ]);

                    $email = (new \Symfony\Component\Mime\Email())
                        ->from(new Address('no-reply@brux.at', 'Brux OT'))
                        ->to($user->getEmail())
                        ->subject($rendered->subject)
                        ->html($rendered->html);

                    if ($rendered->text) {
                        $email->text($rendered->text);
                    }

                    $mailer->send($email);
                } catch (\Throwable $e) {
                    // Fallback: vorhandenes Twig-File weiterverwenden
                    $logger->error('Mail-Template-Rendering fehlgeschlagen', ['exception' => $e]);
                }
            }

            $this->addFlash('success', $t->trans('Wenn die E-Mail existiert, wurde ein Link verschickt.'));
            return $this->redirectToRoute('forgot_password');
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $users->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->isResetTokenValid($token)) {
            $this->addFlash('error', 'Token ist ungültig oder abgelaufen.');
            return $this->redirectToRoute('forgot_password');
        }

        $model = new ResetPasswordData();
        $form = $this->createForm(ResetPasswordType::class, $model);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $model->plainPassword));
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $em->flush();

            $this->addFlash('success', 'Passwort erfolgreich geändert.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
        ]);
    }
}
