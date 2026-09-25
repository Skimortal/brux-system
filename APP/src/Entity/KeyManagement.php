<?php

namespace App\Entity;

use App\Enum\KeyStatus;
use App\Repository\KeyManagementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A physical key. Who holds it and when is tracked by its loans.
 */
#[ORM\Entity(repositoryClass: KeyManagementRepository::class)]
class KeyManagement extends Base
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Room::class, inversedBy: 'keys')]
    #[ORM\JoinTable(name: 'key_management_room')]
    #[ORM\OrderBy(['position' => 'ASC', 'name' => 'ASC'])]
    private Collection $rooms;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $lost = false;

    /** @var Collection<int, KeyLoan> */
    #[ORM\OneToMany(targetEntity: KeyLoan::class, mappedBy: 'key', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['startAt' => 'DESC'])]
    private Collection $loans;

    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->loans = new ArrayCollection();
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

    /** @return Collection<int, Room> */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Room $room): static
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms->add($room);
        }

        return $this;
    }

    public function removeRoom(Room $room): static
    {
        $this->rooms->removeElement($room);

        return $this;
    }

    public function getRoomNames(): string
    {
        return implode(', ', $this->rooms->map(static fn (Room $r) => $r->getName())->toArray());
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isLost(): bool
    {
        return $this->lost;
    }

    public function setLost(bool $lost): static
    {
        $this->lost = $lost;

        return $this;
    }

    /** @return Collection<int, KeyLoan> newest first */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(KeyLoan $loan): static
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setKey($this);
        }

        return $this;
    }

    public function removeLoan(KeyLoan $loan): static
    {
        $this->loans->removeElement($loan);

        return $this;
    }

    /** The loan the key is currently out on, if any. */
    public function getActiveLoan(?\DateTimeInterface $now = null): ?KeyLoan
    {
        $now ??= new \DateTimeImmutable();
        foreach ($this->loans as $loan) {
            if ($loan->isActiveAt($now)) {
                return $loan;
            }
        }

        return null;
    }

    /** The next planned handover that has not started yet. */
    public function getNextLoan(?\DateTimeInterface $now = null): ?KeyLoan
    {
        $now ??= new \DateTimeImmutable();
        $next = null;
        foreach ($this->loans as $loan) {
            if ($loan->isPlannedAt($now) && ($next === null || $loan->getStartAt() < $next->getStartAt())) {
                $next = $loan;
            }
        }

        return $next;
    }

    public function getStatus(?\DateTimeInterface $now = null): KeyStatus
    {
        if ($this->lost) {
            return KeyStatus::LOST;
        }

        return $this->getActiveLoan($now) !== null ? KeyStatus::BORROWED : KeyStatus::AVAILABLE;
    }

    public function isOverdue(?\DateTimeInterface $now = null): bool
    {
        return $this->getActiveLoan($now)?->isOverdue($now) ?? false;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
