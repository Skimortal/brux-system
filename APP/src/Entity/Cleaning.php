<?php

namespace App\Entity;

use App\Repository\CleaningRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CleaningRepository::class)]
class Cleaning extends Base
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cleaningDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cleaningType = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $generalAreas = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $blackRoom = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $whiteRoom = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $backstageToilets = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $dressingRoom = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $backstageCorridor = false;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $officeGroundFloor = false;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getCleaningDate(): ?\DateTimeInterface
    {
        return $this->cleaningDate;
    }

    public function setCleaningDate(?\DateTimeInterface $cleaningDate): static
    {
        $this->cleaningDate = $cleaningDate;

        return $this;
    }

    public function getCleaningType(): ?string
    {
        return $this->cleaningType;
    }

    public function setCleaningType(?string $cleaningType): static
    {
        $this->cleaningType = $cleaningType;

        return $this;
    }

    public function isGeneralAreas(): ?bool
    {
        return $this->generalAreas;
    }

    public function setGeneralAreas(?bool $generalAreas): static
    {
        $this->generalAreas = $generalAreas;

        return $this;
    }

    public function isBlackRoom(): ?bool
    {
        return $this->blackRoom;
    }

    public function setBlackRoom(?bool $blackRoom): static
    {
        $this->blackRoom = $blackRoom;

        return $this;
    }

    public function isWhiteRoom(): ?bool
    {
        return $this->whiteRoom;
    }

    public function setWhiteRoom(?bool $whiteRoom): static
    {
        $this->whiteRoom = $whiteRoom;

        return $this;
    }

    public function isBackstageToilets(): ?bool
    {
        return $this->backstageToilets;
    }

    public function setBackstageToilets(?bool $backstageToilets): static
    {
        $this->backstageToilets = $backstageToilets;

        return $this;
    }

    public function isDressingRoom(): ?bool
    {
        return $this->dressingRoom;
    }

    public function setDressingRoom(?bool $dressingRoom): static
    {
        $this->dressingRoom = $dressingRoom;

        return $this;
    }

    public function isBackstageCorridor(): ?bool
    {
        return $this->backstageCorridor;
    }

    public function setBackstageCorridor(?bool $backstageCorridor): static
    {
        $this->backstageCorridor = $backstageCorridor;

        return $this;
    }

    public function isOfficeGroundFloor(): ?bool
    {
        return $this->officeGroundFloor;
    }

    public function setOfficeGroundFloor(?bool $officeGroundFloor): static
    {
        $this->officeGroundFloor = $officeGroundFloor;

        return $this;
    }
}
