<?php

namespace App\Entity;

use App\Repository\ProductionPriceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionPriceRepository::class)]
class ProductionPrice extends Base
{
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $priceIndex = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $priceLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categoryLabel = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $parentReserved = null;

    #[ORM\ManyToOne(targetEntity: Production::class, inversedBy: 'priceList')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Production $production = null;

    public function getPriceIndex(): ?int
    {
        return $this->priceIndex;
    }

    public function setPriceIndex(?int $priceIndex): static
    {
        $this->priceIndex = $priceIndex;

        return $this;
    }

    public function getPriceLabel(): ?string
    {
        return $this->priceLabel;
    }

    public function setPriceLabel(?string $priceLabel): static
    {
        $this->priceLabel = $priceLabel;

        return $this;
    }

    public function getCategoryLabel(): ?string
    {
        return $this->categoryLabel;
    }

    public function setCategoryLabel(?string $categoryLabel): static
    {
        $this->categoryLabel = $categoryLabel;

        return $this;
    }

    public function getParentReserved(): ?int
    {
        return $this->parentReserved;
    }

    public function setParentReserved(?int $parentReserved): static
    {
        $this->parentReserved = $parentReserved;

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

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->categoryLabel ?? '', $this->priceLabel ?? '');
    }
}
