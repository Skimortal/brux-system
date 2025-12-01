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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cleaningType = null;

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

    public function getCleaningType(): ?string
    {
        return $this->cleaningType;
    }

    public function setCleaningType(?string $cleaningType): static
    {
        $this->cleaningType = $cleaningType;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
