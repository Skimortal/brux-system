<?php

namespace App\Controller\Admin;

use App\Controller\CrudTrait;
use App\Entity\EmailTemplate;
use App\Form\EmailTemplateType;
use App\Repository\EmailTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Templates are rendered as Twig, so editing is limited to superadmins.
 */
#[Route('/verwaltung/e-mail-vorlagen')]
#[IsGranted('ROLE_SUPERUSER')]
final class EmailTemplateController extends AbstractController
{
    use CrudTrait;

    #[Route('', name: 'app_email_template_index', methods: ['GET'])]
    public function index(EmailTemplateRepository $templates): Response
    {
        return $this->render('crud/list.html.twig', [
            'title' => 'E-Mail-Vorlagen',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')]],
            'new_url' => $this->generateUrl('app_email_template_new'),
            'columns' => ['Vorlage', 'Sprache', 'Betreff'],
            'rows' => array_map(fn (EmailTemplate $t) => [
                'url' => $this->generateUrl('app_email_template_edit', ['id' => $t->getId()]),
                'cells' => [$t->getTemplateKey()->label(), $t->getLocale(), $t->getSubjectTemplate()],
            ], $templates->findAll()),
        ]);
    }

    #[Route('/neu', name: 'app_email_template_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        return $this->form(new EmailTemplate(), $request, $em);
    }

    #[Route('/{id}', name: 'app_email_template_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(EmailTemplate $template, Request $request, EntityManagerInterface $em): Response
    {
        return $this->form($template, $request, $em);
    }

    #[Route('/{id}/loeschen', name: 'app_email_template_delete', methods: ['POST'])]
    public function delete(EmailTemplate $template, Request $request, EntityManagerInterface $em): Response
    {
        return $this->deleteEntity($request, $em, $template, 'delete-template-' . $template->getId(), 'app_email_template_index', 'Vorlage');
    }

    private function form(EmailTemplate $template, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EmailTemplateType::class, $template);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($template);
            $em->flush();
            $this->addFlash('success', 'Vorlage gespeichert.');

            return $this->redirectToRoute('app_email_template_index', [], 303);
        }

        return $this->render('crud/edit.html.twig', [
            'title' => $template->getId() ? $template->getTemplateKey()->label() : 'Neue Vorlage',
            'crumbs' => [['label' => 'Verwaltung', 'url' => $this->generateUrl('app_settings')], ['label' => 'E-Mail-Vorlagen', 'url' => $this->generateUrl('app_email_template_index')]],
            'form' => $form,
            'back_url' => $this->generateUrl('app_email_template_index'),
            'delete_url' => $template->getId() ? $this->generateUrl('app_email_template_delete', ['id' => $template->getId()]) : null,
            'delete_token' => 'delete-template-' . $template->getId(),
        ], new Response(null, $form->isSubmitted() ? 422 : 200));
    }
}
