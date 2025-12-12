<?php

namespace App\Entity;

use App\Enum\VolunteerTaskEnum;
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

    #[ORM\ManyToOne(targetEntity: Appointment::class, inversedBy: 'appointmentVolunteers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Appointment $appointment = null;

    #[ORM\ManyToOne(targetEntity: Volunteer::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Volunteer $volunteer = null;

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

    public function getVolunteer(): ?Volunteer
    {
        return $this->volunteer;
    }

    public function setVolunteer(?Volunteer $volunteer): static
    {
        $this->volunteer = $volunteer;
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

    public function getTasks(): ?array
    {
        return $this->tasks;
    }

    public function setTasks(?array $tasks): static
    {
        $this->tasks = $tasks;
        return $this;
    }

    /**
     * @return VolunteerTaskEnum[]
     */
    public function getTasksAsEnums(): array
    {
        if (!$this->tasks) {
            return [];
        }

        return array_map(
            fn($task) => VolunteerTaskEnum::from($task),
            $this->tasks
        );
    }

    public function setTasksFromEnums(array $enums): static
    {
        $this->tasks = array_map(
            fn(VolunteerTaskEnum $enum) => $enum->value,
            $enums
        );
        return $this;
    }
}
