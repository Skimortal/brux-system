<?php

namespace App\Entity;

use App\Repository\ProductionEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionEventRepository::class)]
class ProductionEvent extends Base
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $presenceStartDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $presenceEndDate = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $performanceDates = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rehearsalDates = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $setupDates = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $teardownDates = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $generalRehearsalDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $photoSessionDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $keyHandoverDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $keyReturnDate = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $mainRehearsals = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $photos = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $trailer = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $projectDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $infoTexts = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $desiredTicketPrices = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $creditsAndBios = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $technicalRider = null;

    /**
     * @var Collection<int, ProductionTechnician>
     */
    #[ORM\ManyToMany(targetEntity: ProductionTechnician::class)]
    private Collection $externalTechnicians;

    #[ORM\ManyToOne(targetEntity: Production::class)]
    private ?Production $production = null;

    public function __construct()
    {
        $this->externalTechnicians = new ArrayCollection();
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

    public function getPresenceStartDate(): ?\DateTimeInterface
    {
        return $this->presenceStartDate;
    }

    public function setPresenceStartDate(?\DateTimeInterface $presenceStartDate): static
    {
        $this->presenceStartDate = $presenceStartDate;

        return $this;
    }

    public function getPresenceEndDate(): ?\DateTimeInterface
    {
        return $this->presenceEndDate;
    }

    public function setPresenceEndDate(?\DateTimeInterface $presenceEndDate): static
    {
        $this->presenceEndDate = $presenceEndDate;

        return $this;
    }

    public function getPerformanceDates(): ?array
    {
        return $this->performanceDates;
    }

    public function setPerformanceDates(?array $performanceDates): static
    {
        $this->performanceDates = $performanceDates;

        return $this;
    }

    public function getRehearsalDates(): ?array
    {
        return $this->rehearsalDates;
    }

    public function setRehearsalDates(?array $rehearsalDates): static
    {
        $this->rehearsalDates = $rehearsalDates;

        return $this;
    }

    public function getSetupDates(): ?array
    {
        return $this->setupDates;
    }

    public function setSetupDates(?array $setupDates): static
    {
        $this->setupDates = $setupDates;

        return $this;
    }

    public function getTeardownDates(): ?array
    {
        return $this->teardownDates;
    }

    public function setTeardownDates(?array $teardownDates): static
    {
        $this->teardownDates = $teardownDates;

        return $this;
    }

    public function getGeneralRehearsalDate(): ?\DateTimeInterface
    {
        return $this->generalRehearsalDate;
    }

    public function setGeneralRehearsalDate(?\DateTimeInterface $generalRehearsalDate): static
    {
        $this->generalRehearsalDate = $generalRehearsalDate;

        return $this;
    }

    public function getPhotoSessionDate(): ?\DateTimeInterface
    {
        return $this->photoSessionDate;
    }

    public function setPhotoSessionDate(?\DateTimeInterface $photoSessionDate): static
    {
        $this->photoSessionDate = $photoSessionDate;

        return $this;
    }

    public function getKeyHandoverDate(): ?\DateTimeInterface
    {
        return $this->keyHandoverDate;
    }

    public function setKeyHandoverDate(?\DateTimeInterface $keyHandoverDate): static
    {
        $this->keyHandoverDate = $keyHandoverDate;

        return $this;
    }

    public function getKeyReturnDate(): ?\DateTimeInterface
    {
        return $this->keyReturnDate;
    }

    public function setKeyReturnDate(?\DateTimeInterface $keyReturnDate): static
    {
        $this->keyReturnDate = $keyReturnDate;

        return $this;
    }

    public function getMainRehearsals(): ?array
    {
        return $this->mainRehearsals;
    }

    public function setMainRehearsals(?array $mainRehearsals): static
    {
        $this->mainRehearsals = $mainRehearsals;

        return $this;
    }

    public function getPhotos(): ?array
    {
        return $this->photos;
    }

    public function setPhotos(?array $photos): static
    {
        $this->photos = $photos;

        return $this;
    }

    public function getTrailer(): ?string
    {
        return $this->trailer;
    }

    public function setTrailer(?string $trailer): static
    {
        $this->trailer = $trailer;

        return $this;
    }

    public function getProjectDescription(): ?string
    {
        return $this->projectDescription;
    }

    public function setProjectDescription(?string $projectDescription): static
    {
        $this->projectDescription = $projectDescription;

        return $this;
    }

    public function getInfoTexts(): ?string
    {
        return $this->infoTexts;
    }

    public function setInfoTexts(?string $infoTexts): static
    {
        $this->infoTexts = $infoTexts;

        return $this;
    }

    public function getDesiredTicketPrices(): ?string
    {
        return $this->desiredTicketPrices;
    }

    public function setDesiredTicketPrices(?string $desiredTicketPrices): static
    {
        $this->desiredTicketPrices = $desiredTicketPrices;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getCreditsAndBios(): ?string
    {
        return $this->creditsAndBios;
    }

    public function setCreditsAndBios(?string $creditsAndBios): static
    {
        $this->creditsAndBios = $creditsAndBios;

        return $this;
    }

    public function getTechnicalRider(): ?string
    {
        return $this->technicalRider;
    }

    public function setTechnicalRider(?string $technicalRider): static
    {
        $this->technicalRider = $technicalRider;

        return $this;
    }

    /**
     * @return Collection<int, ProductionTechnician>
     */
    public function getExternalTechnicians(): Collection
    {
        return $this->externalTechnicians;
    }

    public function addExternalTechnician(ProductionTechnician $externalTechnician): static
    {
        if (!$this->externalTechnicians->contains($externalTechnician)) {
            $this->externalTechnicians->add($externalTechnician);
        }

        return $this;
    }

    public function removeExternalTechnician(ProductionTechnician $externalTechnician): static
    {
        $this->externalTechnicians->removeElement($externalTechnician);

        return $this;
    }

    public function getProduction(): ?Production
    {
        return $this->production;
    }

    public function setProduction(?Production $production): static
    {
        $this->production = $production;

        return $this;
    }
}
