<?php

namespace App\Entity;

use App\Enum\TechnicianStatus;
use App\Repository\AppointmentTechnicianRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentTechnicianRepository::class)]
class AppointmentTechnician
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'appointmentTechnicians')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Appointment $appointment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $contact = null;

    #[ORM\Column(length: 20, enumType: TechnicianStatus::class, options: ['default' => 'assigned'])]
    private TechnicianStatus $status = TechnicianStatus::ASSIGNED;

    /** When the technician last answered (accepted, declined or showed interest). */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    #[ORM\Column]
    private bool $lighting = false;

    #[ORM\Column]
    private bool $sound = false;

    #[ORM\Column]
    private bool $setup = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __clone()
    {
        $this->id = null;
        $this->appointment = null;
    }

    /** @return string[] */
    public function getRoleLabels(): array
    {
        return array_keys(array_filter(['Licht' => $this->lighting, 'Ton' => $this->sound, 'Aufbau' => $this->setup]));
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

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;
        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->status === TechnicianStatus::CONFIRMED;
    }

    public function getStatus(): TechnicianStatus
    {
        return $this->status;
    }

    public function setStatus(TechnicianStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    /** The technician's own answer from their area or an e-mail link. */
    public function respond(TechnicianStatus $status, \DateTimeImmutable $at): static
    {
        $this->status = $status;
        $this->respondedAt = $at;

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
