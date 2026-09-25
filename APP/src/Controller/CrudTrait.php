<?php

namespace App\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Shared delete handling for the administration pages: POST + CSRF token,
 * with a readable message when the record is still in use.
 */
trait CrudTrait
{
    private function deleteEntity(Request $request, EntityManagerInterface $em, object $entity, string $tokenId, string $redirectRoute, string $label): RedirectResponse
    {
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            throw new AccessDeniedHttpException('Ungültiges Formular.');
        }
        try {
            $em->remove($entity);
            $em->flush();
            $this->addFlash('success', $label . ' gelöscht.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('danger', $label . ' wird noch verwendet und kann nicht gelöscht werden.');
        }

        return $this->redirectToRoute($redirectRoute, [], 303);
    }
}
