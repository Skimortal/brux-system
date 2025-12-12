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
}
