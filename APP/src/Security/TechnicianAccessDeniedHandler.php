<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * Technicians who open a page of the office land in their own area instead of on an
 * error page; everyone else keeps the usual "access denied".
 */
final class TechnicianAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private readonly Security $security, private readonly UrlGeneratorInterface $urls)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->isTechnician() || !$request->isMethod('GET')) {
            return null;
        }
        $session = $request->hasSession() ? $request->getSession() : null;
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('warning', 'Diese Seite ist nur für das Büro. Hier siehst du alle Termine und deine Einsätze.');
        }

        return new RedirectResponse($this->urls->generate('app_technician'));
    }
}
