<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Entity\Todo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/todos')]
final class TodoController extends AbstractController
{
    use CalendarActionTrait;

    public function __construct(
        private readonly CalendarPage $page,
        private readonly EntityManagerInterface $em,
        private readonly CsrfTokenManagerInterface $csrf,
    ) {
    }

    #[Route('', name: 'app_todo_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'todo');
        $description = trim($request->request->getString('description'));
        if ($description !== '') {
            $todo = (new Todo())->setDescription(mb_substr($description, 0, 255));
            $this->em->persist($todo);
            $this->em->flush();
        }

        return $this->backToCalendar($request, $this->page, ['side' => 'todos']);
    }

    #[Route('/{id}', name: 'app_todo_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function update(Todo $todo, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'todo');
        $description = trim($request->request->getString('description'));
        if ($description !== '') {
            $todo->setDescription(mb_substr($description, 0, 255));
            $this->em->flush();
        }

        return $this->backToCalendar($request, $this->page, ['side' => 'todos']);
    }

    #[Route('/{id}/erledigt', name: 'app_todo_done', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function done(Todo $todo, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'todo');
        $todo->setDone(true);
        $this->em->flush();
        $this->addFlash('success', 'Todo erledigt.');

        return $this->backToCalendar($request, $this->page, ['side' => 'todos']);
    }
}
