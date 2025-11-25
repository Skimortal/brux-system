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
    #[ORM\JoinColumn(nullable: true)]
    private ?Room $room = null;

    #[ORM\Column(length: 255)]
    private ?string $keyColor = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: KeyStatus::class)]
    private ?KeyStatus $status = KeyStatus::AVAILABLE;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $borrowDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $returnDate = null;

    // Neue Relationen
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Technician::class)]
    private ?Technician $technician = null;

    #[ORM\ManyToOne(targetEntity: Production::class)]
    private ?Production $production = null;

    // Falls Cleaning eine Entity ist:
    #[ORM\ManyToOne(targetEntity: Cleaning::class)]
    private ?Cleaning $cleaning = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTechnician(): ?Technician
    {
        return $this->technician;
    }

    public function setTechnician(?Technician $technician): static
    {
        $this->technician = $technician;
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

    public function getCleaning(): ?Cleaning
    {
        return $this->cleaning;
    }

    public function setCleaning(?Cleaning $cleaning): static
    {
        $this->cleaning = $cleaning;
        return $this;
    }

    // Helper Methode um den aktuellen Besitzer anzuzeigen
    public function getCurrentHolderName(): string
    {
        if ($this->user) return $this->user->getEmail(); // Oder ->getName() falls vorhanden
        if ($this->technician) return $this->technician->getName();
        if ($this->production) return $this->production->getDisplayName();
        if ($this->cleaning) return 'Reinigung'; // Oder $this->cleaning->getName()
        return 'Unbekannt';
    }

}
