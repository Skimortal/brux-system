<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room extends Base
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $externalId = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $showOnDashboard = false;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: KeyManagement::class)]
    private Collection $keys;

    public function __construct()
    {
        $this->keys = new ArrayCollection();
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

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function isShowOnDashboard(): bool
    {
        return $this->showOnDashboard;
    }

    public function setShowOnDashboard(bool $showOnDashboard): static
    {
        $this->showOnDashboard = $showOnDashboard;

        return $this;
    }

    /**
     * @return Collection<int, KeyManagement>
     */
    public function getKeys(): Collection
    {
        return $this->keys;
    }

    public function addKey(KeyManagement $key): static
    {
        if (!$this->keys->contains($key)) {
            $this->keys->add($key);
            $key->setRoom($this);
        }

        return $this;
    }

    public function removeKey(KeyManagement $key): static
    {
        if ($this->keys->removeElement($key)) {
            if ($key->getRoom() === $this) {
                $key->setRoom(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
