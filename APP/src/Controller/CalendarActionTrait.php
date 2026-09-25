<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Helpers for POST actions triggered from the calendar side panel.
 */
trait CalendarActionTrait
{
    /**
     * Redirects back to the calendar the form came from, optionally changing the panel.
     *
     * @param array<string, string|null> $overrides
     */
    private function backToCalendar(Request $request, CalendarPage $page, array $overrides = []): RedirectResponse
    {
        return new RedirectResponse($page->url($page->queryFromReturn($request), $overrides), Response::HTTP_SEE_OTHER);
    }

    private function requireCsrf(Request $request, CsrfTokenManagerInterface $csrf, string $id): void
    {
        if (!$csrf->isTokenValid(new CsrfToken($id, (string) $request->request->get('_token')))) {
            throw new AccessDeniedHttpException('Ungültiges Formular. Bitte Seite neu laden.');
        }
    }
}
