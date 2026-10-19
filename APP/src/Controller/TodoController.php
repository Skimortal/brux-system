<?php

namespace App\Controller;

use App\Calendar\CalendarPage;
use App\Entity\Todo;
use App\Entity\TodoGroup;
use App\Repository\TodoGroupRepository;
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
        private readonly TodoGroupRepository $groups,
    ) {
    }

    #[Route('', name: 'app_todo_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'todo');
        $description = trim($request->request->getString('description'));
        if ($description !== '') {
            $todo = (new Todo())->setDescription(mb_substr($description, 0, 255));
            $groupId = $request->request->getInt('group');
            if ($groupId > 0) {
                $todo->setGroup($this->groups->find($groupId));
            }
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

    #[Route('/gruppen', name: 'app_todo_group_create', methods: ['POST'])]
    public function createGroup(Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'todo');
        $name = trim($request->request->getString('name'));
        if ($name !== '') {
            $this->em->persist((new TodoGroup())->setName(mb_substr($name, 0, 100)));
            $this->em->flush();
        }

        return $this->backToCalendar($request, $this->page, ['side' => 'todos']);
    }

    #[Route('/gruppen/{id}', name: 'app_todo_group_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateGroup(TodoGroup $group, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'todo');
        $name = trim($request->request->getString('name'));
        if ($name !== '') {
            $group->setName(mb_substr($name, 0, 100));
            $this->em->flush();
        }

        return $this->backToCalendar($request, $this->page, ['side' => 'todos']);
    }

    #[Route('/gruppen/{id}/loeschen', name: 'app_todo_group_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteGroup(TodoGroup $group, Request $request): Response
    {
        $this->requireCsrf($request, $this->csrf, 'todo');
        // its todos stay, as general ones (ON DELETE SET NULL)
        foreach ($group->getOpenTodos() as $todo) {
            $todo->setGroup(null);
        }
        $this->em->remove($group);
        $this->em->flush();
        $this->addFlash('success', 'Gruppe gelöscht, ihre Todos sind jetzt unter „Allgemein“.');

        return $this->backToCalendar($request, $this->page, ['side' => 'todos']);
    }
}
