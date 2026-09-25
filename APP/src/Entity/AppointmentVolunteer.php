<?php

namespace App\Entity;

use App\Repository\AppointmentVolunteerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentVolunteerRepository::class)]
class AppointmentVolunteer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'appointmentVolunteers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Appointment $appointment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $contact = null;

    #[ORM\Column]
    private bool $confirmed = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $tasks = [];

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
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): static
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    /** @return \App\Enum\VolunteerTaskEnum[] */
    public function getTaskEnums(): array
    {
        return array_values(array_filter(array_map(
            static fn ($t) => \is_string($t) ? \App\Enum\VolunteerTaskEnum::tryFrom($t) : null,
            $this->tasks ?? []
        )));
    }

    /** @param \App\Enum\VolunteerTaskEnum[] $tasks */
    public function setTaskEnums(array $tasks): static
    {
        $this->tasks = array_values(array_map(static fn (\App\Enum\VolunteerTaskEnum $t) => $t->value, $tasks));

        return $this;
    }

    public function __clone()
    {
        $this->id = null;
        $this->appointment = null;
    }

    public function getTasks(): ?array
    {
        return $this->tasks;
    }

    public function setTasks(?array $tasks): static
    {
        $this->tasks = $tasks;
        return $this;
    }
}
