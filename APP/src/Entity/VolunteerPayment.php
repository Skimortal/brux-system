<?php

namespace App\Entity;

use App\Repository\VolunteerPaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VolunteerPaymentRepository::class)]
class VolunteerPayment extends Base
{
    #[ORM\ManyToOne(targetEntity: Volunteer::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Volunteer $volunteer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $proofDocument = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getVolunteer(): ?Volunteer
    {
        return $this->volunteer;
    }

    public function setVolunteer(?Volunteer $volunteer): static
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeInterface $paymentDate): static
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getProofDocument(): ?string
    {
        return $this->proofDocument;
    }

    public function setProofDocument(?string $proofDocument): static
    {
        $this->proofDocument = $proofDocument;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
