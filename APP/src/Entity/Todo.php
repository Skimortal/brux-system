<?php

namespace App\Entity;

use App\Repository\TodoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TodoRepository::class)]
class Todo extends Base
{
    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $done = false;

    /** When it was ticked off; todos done before this was recorded fall back to their last change. */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $doneAt = null;

    /** Deleting a group keeps its todos as general ones. */
    #[ORM\ManyToOne(inversedBy: 'todos')]
    #[ORM\JoinColumn(name: 'todo_group_id', onDelete: 'SET NULL')]
    private ?TodoGroup $group = null;

    public function getGroup(): ?TodoGroup
    {
        return $this->group;
    }

    public function setGroup(?TodoGroup $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isDone(): bool
    {
        return $this->done;
    }

    public function setDone(bool $done): void
    {
        $this->done = $done;
        $this->doneAt = $done ? new \DateTimeImmutable() : null;
    }

    public function getDoneAt(): ?\DateTimeInterface
    {
        return $this->doneAt ?? $this->updatedAt ?? $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->description ?? '';
    }

}
