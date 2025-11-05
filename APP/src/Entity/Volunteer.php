<?php

namespace App\Entity;

use App\Repository\VolunteerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VolunteerRepository::class)]
class Volunteer extends Base
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    /**
     * @var Collection<int, VolunteerPayment>
     */
    #[ORM\OneToMany(targetEntity: VolunteerPayment::class, mappedBy: 'volunteer', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $payments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection<int, VolunteerPayment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(VolunteerPayment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setVolunteer($this);
        }

        return $this;
    }

    public function removePayment(VolunteerPayment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getVolunteer() === $this) {
                $payment->setVolunteer(null);
            }
        }

        return $this;
    }

    public function getTotalPaid(): float
    {
        $total = 0;
        foreach ($this->payments as $payment) {
            $total += $payment->getAmount();
        }
        return $total;
    }
}
