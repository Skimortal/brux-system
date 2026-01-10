<?php

namespace App\Entity;

use App\Repository\ProductionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionRepository::class)]
class Production extends Base
{
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $externalId = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $permalink = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $postThumbnailUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contentHtml = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $excerptHtml = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $needsLightingTechnician = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $needsSoundTechnician = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $needsSetupTechnician = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $grandstand = null;

    #[ORM\OneToMany(targetEntity: ProductionTechnician::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $technicians;

    #[ORM\OneToMany(targetEntity: ProductionContactPerson::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $contactPersons;

    #[ORM\OneToMany(targetEntity: ProductionEvent::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $events;

    #[ORM\OneToMany(targetEntity: ProductionPrice::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $priceList;

    public function __construct()
    {
        $this->technicians = new ArrayCollection();
        $this->contactPersons = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->priceList = new ArrayCollection();
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(?int $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPermalink(): ?string
    {
        return $this->permalink;
    }

    public function setPermalink(?string $permalink): static
    {
        $this->permalink = $permalink;

        return $this;
    }

    public function getPostThumbnailUrl(): ?string
    {
        return $this->postThumbnailUrl;
    }

    public function setPostThumbnailUrl(?string $postThumbnailUrl): static
    {
        $this->postThumbnailUrl = $postThumbnailUrl;

        return $this;
    }

    public function getContentHtml(): ?string
    {
        return $this->contentHtml;
    }

    public function setContentHtml(?string $contentHtml): static
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }

    public function getExcerptHtml(): ?string
    {
        return $this->excerptHtml;
    }

    public function setExcerptHtml(?string $excerptHtml): static
    {
        $this->excerptHtml = $excerptHtml;

        return $this;
    }

    public function isNeedsLightingTechnician(): bool
    {
        return $this->needsLightingTechnician;
    }

    public function setNeedsLightingTechnician(bool $needsLightingTechnician): static
    {
        $this->needsLightingTechnician = $needsLightingTechnician;

        return $this;
    }

    public function isNeedsSoundTechnician(): bool
    {
        return $this->needsSoundTechnician;
    }

    public function setNeedsSoundTechnician(bool $needsSoundTechnician): static
    {
        $this->needsSoundTechnician = $needsSoundTechnician;

        return $this;
    }

    public function isNeedsSetupTechnician(): bool
    {
        return $this->needsSetupTechnician;
    }

    public function setNeedsSetupTechnician(bool $needsSetupTechnician): static
    {
        $this->needsSetupTechnician = $needsSetupTechnician;

        return $this;
    }

    public function getGrandstand(): ?string
    {
        return $this->grandstand;
    }

    public function setGrandstand(?string $grandstand): static
    {
        $this->grandstand = $grandstand;

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
            if ($event->getProduction() === $this) {
                $event->setProduction(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductionContactPerson>
     */
    public function getContactPersons(): Collection
    {
        return $this->contactPersons;
    }

    public function addContactPerson(ProductionContactPerson $contactPerson): static
    {
        if (!$this->contactPersons->contains($contactPerson)) {
            $this->contactPersons->add($contactPerson);
            $contactPerson->setProduction($this);
        }

        return $this;
    }

    public function removeContactPerson(ProductionContactPerson $contactPerson): static
    {
        if ($this->contactPersons->removeElement($contactPerson)) {
            if ($contactPerson->getProduction() === $this) {
                $contactPerson->setProduction(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductionPrice>
     */
    public function getPriceList(): Collection
    {
        return $this->priceList;
    }

    public function addPriceList(ProductionPrice $priceList): static
    {
        if (!$this->priceList->contains($priceList)) {
            $this->priceList->add($priceList);
            $priceList->setProduction($this);
        }

        return $this;
    }

    public function removePriceList(ProductionPrice $priceList): static
    {
        if ($this->priceList->removeElement($priceList)) {
            if ($priceList->getProduction() === $this) {
                $priceList->setProduction(null);
            }
        }

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->title ?? '';
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }
}
