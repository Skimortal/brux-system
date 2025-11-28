<?php

namespace App\Entity;

use App\Repository\EventPriceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventPriceRepository::class)]
class EventPrice extends Base
{
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $priceIndex = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $priceLabel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categoryLabel = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $reservedSeats = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $incomingReservations = null;

    #[ORM\ManyToOne(targetEntity: ProductionEvent::class, inversedBy: 'priceList')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductionEvent $event = null;

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

    public function getReservedSeats(): ?int
    {
        return $this->reservedSeats;
    }

    public function setReservedSeats(?int $reservedSeats): static
    {
        $this->reservedSeats = $reservedSeats;

        return $this;
    }

    public function getIncomingReservations(): ?int
    {
        return $this->incomingReservations;
    }

    public function setIncomingReservations(?int $incomingReservations): static
    {
        $this->incomingReservations = $incomingReservations;

        return $this;
    }

    public function getEvent(): ?ProductionEvent
    {
        return $this->event;
    }

    public function setEvent(?ProductionEvent $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->categoryLabel ?? '', $this->priceLabel ?? '');
    }
}
