<?php

namespace App\Entity;

use App\Enum\AppointmentStatusEnum;
use App\Enum\AppointmentTypeEnum;
use App\Enum\EventTypeEnum;
use App\Repository\AppointmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: Cleaning::class)]
    private ?Cleaning $cleaning = null;

    #[ORM\ManyToOne(targetEntity: Production::class)]
    private ?Production $production = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private ?bool $allDay = false;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = '#4285f4';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(enumType: AppointmentTypeEnum::class)]
    private ?AppointmentTypeEnum $type = AppointmentTypeEnum::PRIVATE;


    #[ORM\Column(enumType: EventTypeEnum::class, nullable: true)]
    private ?EventTypeEnum $eventType = null;

    #[ORM\Column(enumType: AppointmentStatusEnum::class, nullable: true)]
    private ?AppointmentStatusEnum $status = null;

    #[ORM\Column]
    private bool $internalTechniciansAttending = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $cleaningOptions = [];

    #[ORM\OneToMany(targetEntity: AppointmentTechnician::class, mappedBy: 'appointment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $appointmentTechnicians;

    #[ORM\OneToMany(targetEntity: AppointmentVolunteer::class, mappedBy: 'appointment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $appointmentVolunteers;

    // Wiederholungen
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?self $parentAppointment = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $recurrenceFrequency = null; // 'daily', 'weekly', 'monthly'

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $recurrenceEndDate = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->appointmentTechnicians = new ArrayCollection();
        $this->appointmentVolunteers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isAllDay(): ?bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): static
    {
        $this->allDay = $allDay;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;
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

    public function getProduction(): ?Production
    {
        return $this->production;
    }

    public function setProduction(?Production $production): static
    {
        $this->production = $production;
        return $this;
    }

    public function getType(): ?AppointmentTypeEnum
    {
        return $this->type;
    }

    public function setType(AppointmentTypeEnum $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getEventType(): ?EventTypeEnum
    {
        return $this->eventType;
    }

    public function setEventType(?EventTypeEnum $eventType): static
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getStatus(): ?AppointmentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(?AppointmentStatusEnum $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isInternalTechniciansAttending(): bool
    {
        return $this->internalTechniciansAttending;
    }

    public function setInternalTechniciansAttending(bool $internalTechniciansAttending): static
    {
        $this->internalTechniciansAttending = $internalTechniciansAttending;
        return $this;
    }

    public function getCleaningOptions(): ?array
    {
        return $this->cleaningOptions;
    }

    public function setCleaningOptions(?array $cleaningOptions): static
    {
        $this->cleaningOptions = $cleaningOptions;
        return $this;
    }

    /**
     * @return Collection<int, AppointmentTechnician>
     */
    public function getAppointmentTechnicians(): Collection
    {
        return $this->appointmentTechnicians;
    }

    public function addAppointmentTechnician(AppointmentTechnician $appointmentTechnician): static
    {
        if (!$this->appointmentTechnicians->contains($appointmentTechnician)) {
            $this->appointmentTechnicians->add($appointmentTechnician);
            $appointmentTechnician->setAppointment($this);
        }
        return $this;
    }

    public function removeAppointmentTechnician(AppointmentTechnician $appointmentTechnician): static
    {
        if ($this->appointmentTechnicians->removeElement($appointmentTechnician)) {
            if ($appointmentTechnician->getAppointment() === $this) {
                $appointmentTechnician->setAppointment(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, AppointmentVolunteer>
     */
    public function getAppointmentVolunteers(): Collection
    {
        return $this->appointmentVolunteers;
    }

    public function addAppointmentVolunteer(AppointmentVolunteer $appointmentVolunteer): static
    {
        if (!$this->appointmentVolunteers->contains($appointmentVolunteer)) {
            $this->appointmentVolunteers->add($appointmentVolunteer);
            $appointmentVolunteer->setAppointment($this);
        }
        return $this;
    }

    public function removeAppointmentVolunteer(AppointmentVolunteer $appointmentVolunteer): static
    {
        if ($this->appointmentVolunteers->removeElement($appointmentVolunteer)) {
            if ($appointmentVolunteer->getAppointment() === $this) {
                $appointmentVolunteer->setAppointment(null);
            }
        }
        return $this;
    }

    public function getParentAppointment(): ?self
    {
        return $this->parentAppointment;
    }

    public function setParentAppointment(?self $parentAppointment): static
    {
        $this->parentAppointment = $parentAppointment;
        return $this;
    }

    public function getRecurrenceFrequency(): ?string
    {
        return $this->recurrenceFrequency;
    }

    public function setRecurrenceFrequency(?string $recurrenceFrequency): static
    {
        $this->recurrenceFrequency = $recurrenceFrequency;
        return $this;
    }

    public function getRecurrenceEndDate(): ?\DateTimeInterface
    {
        return $this->recurrenceEndDate;
    }

    public function setRecurrenceEndDate(?\DateTimeInterface $recurrenceEndDate): static
    {
        $this->recurrenceEndDate = $recurrenceEndDate;
        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->recurrenceFrequency !== null;
    }
}
