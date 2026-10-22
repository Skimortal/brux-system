<?php

namespace App\Entity;

use App\Repository\TodoGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A heading todos are filed under, e.g. "Einkaufsliste". Todos without a group are general ones.
 */
#[ORM\Entity(repositoryClass: TodoGroupRepository::class)]
class TodoGroup extends Base
{
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /** @var Collection<int, Todo> */
    #[ORM\OneToMany(targetEntity: Todo::class, mappedBy: 'group')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $todos;

    public function __construct()
    {
        $this->todos = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return list<Todo>
     */
    public function getOpenTodos(): array
    {
        return array_values($this->todos->filter(static fn (Todo $t) => !$t->isDone())->toArray());
    }

    /**
     * The most recently ticked-off todos, newest first.
     *
     * @return list<Todo>
     */
    public function getDoneTodos(int $limit = 20): array
    {
        $done = array_values($this->todos->filter(static fn (Todo $t) => $t->isDone())->toArray());
        usort($done, static fn (Todo $a, Todo $b) => $b->getDoneAt() <=> $a->getDoneAt());

        return \array_slice($done, 0, $limit);
    }

    public function countDoneTodos(): int
    {
        return $this->todos->filter(static fn (Todo $t) => $t->isDone())->count();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
