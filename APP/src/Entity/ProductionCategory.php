<?php

namespace App\Entity;

use App\Repository\ProductionCategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Category of a production on the website (e.g. VIP, Kinderprogramm), a WordPress
 * category without the "-event" suffix. The colour is only used here, in the calendar.
 */
#[ORM\Entity(repositoryClass: ProductionCategoryRepository::class)]
class ProductionCategory extends Base
{
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $externalId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null;

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(?int $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
