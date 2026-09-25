<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Entity\KeyLoan;
use App\Entity\KeyManagement;
use App\Form\KeyLoanType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/schluessel')]
final class KeyLoanController extends AbstractController
{
    use CalendarActionTrait;

    public function __construct(
        private readonly CalendarPage $page,
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/ausleihe/neu', name: 'app_key_loan_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $loan = new KeyLoan();
        $query = $this->page->queryFromReturn($request);
        // links to an appointment or performance travel as query parameters of the panel URL
        parse_str((string) parse_url((string) $request->request->get('_return'), \PHP_URL_QUERY), $params);
        $defaults = $this->page->newLoan(new Request($params));
        $loan->setAppointment($defaults->getAppointment())->setProductionEvent($defaults->getProductionEvent());

        $form = $this->createForm(KeyLoanType::class, $loan);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($loan);
            $this->em->flush();
            $this->addFlash('success', sprintf('%s an %s eingetragen.', $loan->getKey()?->getName(), $loan->getHolderLabel()));

            return $this->backToCalendar($request, $this->page, ['panel' => 'k-' . $loan->getKey()?->getId(), 'side' => 'keys']);
        }

        return $this->page->render($query, $request, ['loan' => $form], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/ausleihe/{id}', name: 'app_key_loan_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(KeyLoan $loan, Request $request): Response
    {
        $form = $this->createForm(KeyLoanType::class, $loan);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Ausleihe gespeichert.');

            return $this->backToCalendar($request, $this->page, ['panel' => 'k-' . $loan->getKey()?->getId()]);
        }

        return $this->page->render($this->page->queryFromReturn($request), $request, ['loan' => $form], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Route('/ausleihe/{id}/zurueck', name: 'app_key_loan_return', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markReturned(KeyLoan $loan, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'loan-' . $loan->getId());
        $loan->setReturnedAt($this->clock->now());
        $this->em->flush();
        $this->addFlash('success', sprintf('%s ist zurück.', $loan->getKey()?->getName()));

        return $this->backToCalendar($request, $this->page);
    }

    #[Route('/ausleihe/{id}/uebergeben', name: 'app_key_loan_handover', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markHandedOver(KeyLoan $loan, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'loan-' . $loan->getId());
        $loan->setStartAt($this->clock->now());
        $this->em->flush();
        $this->addFlash('success', sprintf('%s an %s übergeben.', $loan->getKey()?->getName(), $loan->getHolderLabel()));

        return $this->backToCalendar($request, $this->page);
    }

    #[Route('/ausleihe/{id}/loeschen', name: 'app_key_loan_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(KeyLoan $loan, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'loan-' . $loan->getId());
        $keyId = $loan->getKey()?->getId();
        $this->em->remove($loan);
        $this->em->flush();
        $this->addFlash('success', 'Ausleihe gelöscht.');

        return $this->backToCalendar($request, $this->page, ['panel' => 'k-' . $keyId]);
    }

    #[Route('/{id}/verloren', name: 'app_key_lost', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleLost(KeyManagement $key, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'key-' . $key->getId());
        $lost = !$key->isLost();
        $key->setLost($lost);
        if ($lost && ($active = $key->getActiveLoan()) !== null) {
            $active->setNote(trim(($active->getNote() ?? '') . "\nAls verloren gemeldet am " . $this->clock->now()->format('d.m.Y')));
        }
        $this->em->flush();
        $this->addFlash($lost ? 'warning' : 'success', $lost ? sprintf('%s als verloren markiert.', $key->getName()) : sprintf('%s ist wieder da.', $key->getName()));

        return $this->backToCalendar($request, $this->page);
    }
}
