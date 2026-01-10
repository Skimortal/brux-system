<?php

namespace App\Entity;

use App\Repository\AppointmentTechnicianRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentTechnicianRepository::class)]
class AppointmentTechnician
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Appointment::class, inversedBy: 'appointmentTechnicians')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Appointment $appointment = null;
    #[ORM\ManyToOne(targetEntity: Technician::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Technician $technician = null;
    #[ORM\Column]
    private bool $confirmed = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $lighting = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $sound = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $setup = false;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): static
    {
        $this->appointment = $appointment;
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

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): static
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    public function isLighting(): bool
    {
        return $this->lighting;
    }

    public function setLighting(bool $lighting): static
    {
        $this->lighting = $lighting;

        return $this;
    }

    public function isSound(): bool
    {
        return $this->sound;
    }

    public function setSound(bool $sound): static
    {
        $this->sound = $sound;

        return $this;
    }

    public function isSetup(): bool
    {
        return $this->setup;
    }

    public function setSetup(bool $setup): static
    {
        $this->setup = $setup;

        return $this;
    }
}
