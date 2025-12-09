<?php

namespace App\Entity;

use App\Repository\ProductionContactPersonRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionContactPersonRepository::class)]
class ProductionContactPerson extends Base
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $hauptansprechperson = false;

    #[ORM\ManyToOne(targetEntity: Production::class, inversedBy: 'contactPersons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Production $production = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function isHauptansprechperson(): bool
    {
        return $this->hauptansprechperson;
    }

    public function setHauptansprechperson(bool $hauptansprechperson): static
    {
        $this->hauptansprechperson = $hauptansprechperson;

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
