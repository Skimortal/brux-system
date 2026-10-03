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
    use WordPressSyncTrait;

    public const GRANDSTAND_LABELS = [
        'with_grandstand' => 'mit Tribüne',
        'without_grandstand' => 'ohne Tribüne',
        'stage' => 'Bühne',
    ];

    /** Publication state of the production's page on the website (WordPress post_status). */
    public const WEBSITE_STATUS_LABELS = [
        'publish' => 'veröffentlicht',
        'draft' => 'Entwurf',
    ];

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

    // sections of the website post, see WebsiteContent
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $generalInfo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $intro = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pressQuotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $credits = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalInfo = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $needsLightingTechnician = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $needsSoundTechnician = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $needsSetupTechnician = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $grandstand = null;

    #[ORM\Column(length: 20, options: ['default' => 'draft'])]
    private string $websiteStatus = 'draft';

    #[ORM\OneToMany(targetEntity: ProductionTechnician::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $technicians;

    #[ORM\OneToMany(targetEntity: ProductionContactPerson::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $contactPersons;

    #[ORM\OneToMany(targetEntity: ProductionEvent::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $events;

    #[ORM\ManyToMany(targetEntity: ProductionCategory::class)]
    #[ORM\JoinTable(name: 'production_production_category')]
    private Collection $categories;

    #[ORM\OneToMany(targetEntity: ProductionPrice::class, mappedBy: 'production', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $priceList;

    public function __construct()
    {
        $this->technicians = new ArrayCollection();
        $this->contactPersons = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->categories = new ArrayCollection();
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

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getGeneralInfo(): ?string
    {
        return $this->generalInfo;
    }

    public function setGeneralInfo(?string $generalInfo): static
    {
        $this->generalInfo = $generalInfo;

        return $this;
    }

    public function getIntro(): ?string
    {
        return $this->intro;
    }

    public function setIntro(?string $intro): static
    {
        $this->intro = $intro;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
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

    public function getPressQuotes(): ?string
    {
        return $this->pressQuotes;
    }

    public function setPressQuotes(?string $pressQuotes): static
    {
        $this->pressQuotes = $pressQuotes;

        return $this;
    }

    public function getCredits(): ?string
    {
        return $this->credits;
    }

    public function setCredits(?string $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): static
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }

    /**
     * @return Collection<int, ProductionCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(ProductionCategory $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(ProductionCategory $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /** The first category with a colour, for the calendar. */
    public function getCalendarColor(): ?string
    {
        foreach ($this->categories as $category) {
            if ($category->getColor() !== null) {
                return $category->getColor();
            }
        }

        return null;
    }

    public function getWebsiteStatus(): string
    {
        return $this->websiteStatus;
    }

    public function setWebsiteStatus(string $websiteStatus): static
    {
        $this->websiteStatus = $websiteStatus;

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

    /** Whether the production originates from the WordPress site. */
    public function isFromWordPress(): bool
    {
        return $this->externalId !== null;
    }

    /**
     * @return ProductionContactPerson|null the main contact person, if any
     */
    public function getMainContactPerson(): ?ProductionContactPerson
    {
        foreach ($this->contactPersons as $person) {
            if ($person->isHauptansprechperson()) {
                return $person;
            }
        }

        return null;
    }

    /** @return string[] labels of the technical requirements */
    public function getRequirementLabels(): array
    {
        $labels = [];
        if ($this->needsLightingTechnician) {
            $labels[] = 'Licht';
        }
        if ($this->needsSoundTechnician) {
            $labels[] = 'Ton';
        }
        if ($this->needsSetupTechnician) {
            $labels[] = 'Aufbau';
        }
        $grandstand = self::GRANDSTAND_LABELS[$this->grandstand] ?? null;
        if ($grandstand !== null) {
            $labels[] = $grandstand;
        }

        return $labels;
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
