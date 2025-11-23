<?php

namespace App\Entity;

use App\Enum\KeyStatus;
use App\Repository\KeyManagementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeyManagementRepository::class)]
class KeyManagement extends Base
{
    #[ORM\ManyToOne(inversedBy: 'keys')]
    #[ORM\JoinColumn(nullable: true)] // Kann nullable sein, falls es Generalschlüssel gibt
    private ?Room $room = null;

    #[ORM\Column(length: 255)]
    private ?string $keyColor = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: KeyStatus::class)]
    private ?KeyStatus $status = KeyStatus::AVAILABLE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $borrowerName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $borrowDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $returnDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signature = null;

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getKeyColor(): ?string
    {
        return $this->keyColor;
    }

    public function setKeyColor(string $keyColor): static
    {
        $this->keyColor = $keyColor;

        return $this;
    }

    public function getStatus(): ?KeyStatus
    {
        return $this->status;
    }

    public function setStatus(KeyStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getBorrowerName(): ?string
    {
        return $this->borrowerName;
    }

    public function setBorrowerName(?string $borrowerName): static
    {
        $this->borrowerName = $borrowerName;

        return $this;
    }

    public function getBorrowDate(): ?\DateTimeInterface
    {
        return $this->borrowDate;
    }

    public function setBorrowDate(?\DateTimeInterface $borrowDate): static
    {
        $this->borrowDate = $borrowDate;

        return $this;
    }

    public function getReturnDate(): ?\DateTimeInterface
    {
        return $this->returnDate;
    }

    public function setReturnDate(?\DateTimeInterface $returnDate): static
    {
        $this->returnDate = $returnDate;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }
}
