<?php

namespace App\Entity;

use App\Enum\ProductionType;
use App\Repository\ProductionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionRepository::class)]
class Production extends Base
{
    #[ORM\Column(type: Types::STRING, length: 50, enumType: ProductionType::class)]
    private ?ProductionType $type = null;

    // Group fields
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $groupName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainContactName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainContactFunction = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $groupMembers = [];

    // Individual person fields
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $personName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $personAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $personPhone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $personEmail = null;

    #[ORM\OneToMany(targetEntity: ProductionTechnician::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $technicians;

    #[ORM\OneToMany(targetEntity: ProductionEvent::class, mappedBy: 'production')]
    private Collection $events;

    public function __construct()
    {
        $this->technicians = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getType(): ?ProductionType
    {
        return $this->type;
    }

    public function setType(ProductionType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getGroupName(): ?string
    {
        return $this->groupName;
    }

    public function setGroupName(?string $groupName): static
    {
        $this->groupName = $groupName;

        return $this;
    }

    public function getMainContactName(): ?string
    {
        return $this->mainContactName;
    }

    public function setMainContactName(?string $mainContactName): static
    {
        $this->mainContactName = $mainContactName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMainContactFunction(): ?string
    {
        return $this->mainContactFunction;
    }

    public function setMainContactFunction(?string $mainContactFunction): static
    {
        $this->mainContactFunction = $mainContactFunction;

        return $this;
    }

    public function getGroupMembers(): ?array
    {
        return $this->groupMembers;
    }

    public function setGroupMembers(?array $groupMembers): static
    {
        $this->groupMembers = $groupMembers;

        return $this;
    }

    public function getPersonName(): ?string
    {
        return $this->personName;
    }

    public function setPersonName(?string $personName): static
    {
        $this->personName = $personName;

        return $this;
    }

    public function getPersonAddress(): ?string
    {
        return $this->personAddress;
    }

    public function setPersonAddress(?string $personAddress): static
    {
        $this->personAddress = $personAddress;

        return $this;
    }

    public function getPersonPhone(): ?string
    {
        return $this->personPhone;
    }

    public function setPersonPhone(?string $personPhone): static
    {
        $this->personPhone = $personPhone;

        return $this;
    }

    public function getPersonEmail(): ?string
    {
        return $this->personEmail;
    }

    public function setPersonEmail(?string $personEmail): static
    {
        $this->personEmail = $personEmail;

        return $this;
    }

    /**
     * @return Collection<int, ProductionTechnician>
     */
    public function getTechnicians(): Collection
    {
        return $this->technicians;
    }

    public function addTechnician(ProductionTechnician $technician): static
    {
        if (!$this->technicians->contains($technician)) {
            $this->technicians->add($technician);
            $technician->setProduction($this);
        }

        return $this;
    }

    public function removeTechnician(ProductionTechnician $technician): static
    {
        if ($this->technicians->removeElement($technician)) {
            // set the owning side to null (unless already changed)
            if ($technician->getProduction() === $this) {
                $technician->setProduction(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductionEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(ProductionEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setProduction($this);
        }

        return $this;
    }

    public function removeEvent(ProductionEvent $event): static
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getProduction() === $this) {
                $event->setProduction(null);
            }
        }

        return $this;
    }

    public function getDisplayName(): string
    {
        if ($this->type === ProductionType::GROUP) {
            return $this->groupName ?? '';
        }
        return $this->personName ?? '';
    }
}
