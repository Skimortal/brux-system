<?php

namespace App\Entity;

use App\Enum\AppointmentStatusEnum;
use App\Enum\AppointmentTypeEnum;
use App\Enum\CleaningArea;
use App\Enum\EventTypeEnum;
use App\Repository\AppointmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\Index(columns: ['start_date'])]
#[ORM\Index(columns: ['end_date'])]
class Appointment
{
    public const RECURRENCE_FREQUENCIES = [
        'daily' => 'Täglich',
        'weekly' => 'Wöchentlich',
        'monthly' => 'Monatlich',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: Production::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Production $production = null;

    /** Set when this appointment holds the staffing of a WordPress performance. */
    #[ORM\OneToOne(targetEntity: ProductionEvent::class, inversedBy: 'appointment')]
    #[ORM\JoinColumn(unique: true, onDelete: 'SET NULL')]
    private ?ProductionEvent $productionEvent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'Bitte einen Beginn angeben.')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'Bitte ein Ende angeben.')]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private bool $allDay = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(enumType: AppointmentTypeEnum::class)]
    private AppointmentTypeEnum $type = AppointmentTypeEnum::PRIVATE;

    #[ORM\Column(enumType: EventTypeEnum::class, nullable: true)]
    private ?EventTypeEnum $eventType = null;

    #[ORM\Column(enumType: AppointmentStatusEnum::class, nullable: true)]
    private ?AppointmentStatusEnum $status = null;

    /** "Externe Techniker kommen" – the production brings its own technicians. */
    #[ORM\Column]
    private bool $internalTechniciansAttending = false;

    /** @var string[] values of CleaningArea */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $cleaningOptions = [];

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Contact $cleaningContact = null;

    /** @var Collection<int, AppointmentTechnician> */
    #[ORM\OneToMany(targetEntity: AppointmentTechnician::class, mappedBy: 'appointment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $appointmentTechnicians;

    /** @var Collection<int, AppointmentVolunteer> */
    #[ORM\OneToMany(targetEntity: AppointmentVolunteer::class, mappedBy: 'appointment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $appointmentVolunteers;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?self $parentAppointment = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $recurrenceFrequency = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $recurrenceEndDate = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->appointmentTechnicians = new ArrayCollection();
        $this->appointmentVolunteers = new ArrayCollection();
    }

    public function __clone()
    {
        $this->id = null;
        $this->createdAt = new \DateTime();
        $this->parentAppointment = null;
        $this->recurrenceFrequency = null;
        $this->recurrenceEndDate = null;
        $this->productionEvent = null;
        $this->startDate = $this->startDate ? clone $this->startDate : null;
        $this->endDate = $this->endDate ? clone $this->endDate : null;

        $technicians = $this->appointmentTechnicians;
        $this->appointmentTechnicians = new ArrayCollection();
        foreach ($technicians as $technician) {
            $this->addAppointmentTechnician(clone $technician);
        }
        $volunteers = $this->appointmentVolunteers;
        $this->appointmentVolunteers = new ArrayCollection();
        foreach ($volunteers as $volunteer) {
            $this->addAppointmentVolunteer(clone $volunteer);
        }
    }

    #[Assert\Callback]
    public function validateByType(ExecutionContextInterface $context): void
    {
        if (!$this->type->hasDerivedTitle() && ($this->title === null || trim($this->title) === '')) {
            $context->buildViolation('Bitte einen Titel eingeben.')->atPath('title')->addViolation();
        }
        if ($this->type === AppointmentTypeEnum::PRODUCTION && $this->production === null) {
            $context->buildViolation('Bitte eine Produktion wählen.')->atPath('production')->addViolation();
        }
        if ($this->type === AppointmentTypeEnum::CLEANING && $this->cleaningContact === null) {
            $context->buildViolation('Bitte eine Reinigungskraft wählen.')->atPath('cleaningContact')->addViolation();
        }
        if ($this->startDate !== null && $this->endDate !== null && $this->endDate < $this->startDate) {
            $context->buildViolation('Das Ende liegt vor dem Beginn.')->atPath('endDate')->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /** Title shown in the calendar; derived for productions and cleanings. */
    public function getDisplayTitle(): string
    {
        return match ($this->type) {
            AppointmentTypeEnum::PRODUCTION => trim(($this->production?->getTitle() ?? 'Produktion') . ($this->eventType ? ' · ' . $this->eventType->label() : '')),
            AppointmentTypeEnum::CLEANING => 'Reinigung' . ($this->cleaningContact ? ' · ' . $this->cleaningContact->getName() : ''),
            default => (string) $this->title,
        };
    }

    /**
     * Keeps the stored title column meaningful for types whose title is derived.
     */
    public function syncDerivedTitle(): void
    {
        if ($this->type->hasDerivedTitle()) {
            $this->title = mb_substr($this->getDisplayTitle(), 0, 255);
        }
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

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

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

    public function getProductionEvent(): ?ProductionEvent
    {
        return $this->productionEvent;
    }

    public function setProductionEvent(?ProductionEvent $productionEvent): static
    {
        if ($this->productionEvent !== null && $this->productionEvent !== $productionEvent) {
            $this->productionEvent->setAppointment(null);
        }
        $this->productionEvent = $productionEvent;
        $productionEvent?->setAppointment($this);

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay;
    }

    public function setAllDay(bool $allDay): static
    {
        $this->allDay = $allDay;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getType(): AppointmentTypeEnum
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

    public function setInternalTechniciansAttending(bool $value): static
    {
        $this->internalTechniciansAttending = $value;

        return $this;
    }

    /** @return CleaningArea[] */
    public function getCleaningAreas(): array
    {
        return array_values(array_filter(array_map(
            static fn ($value) => \is_string($value) ? CleaningArea::tryFrom($value) : null,
            $this->cleaningOptions ?? []
        )));
    }

    /** @param CleaningArea[] $areas */
    public function setCleaningAreas(array $areas): static
    {
        $this->cleaningOptions = array_values(array_map(static fn (CleaningArea $a) => $a->value, $areas));

        return $this;
    }

    public function getCleaningContact(): ?Contact
    {
        return $this->cleaningContact;
    }

    public function setCleaningContact(?Contact $cleaningContact): static
    {
        $this->cleaningContact = $cleaningContact;

        return $this;
    }

    /** @return Collection<int, AppointmentTechnician> */
    public function getAppointmentTechnicians(): Collection
    {
        return $this->appointmentTechnicians;
    }

    public function addAppointmentTechnician(AppointmentTechnician $technician): static
    {
        if (!$this->appointmentTechnicians->contains($technician)) {
            $this->appointmentTechnicians->add($technician);
            $technician->setAppointment($this);
        }

        return $this;
    }

    public function removeAppointmentTechnician(AppointmentTechnician $technician): static
    {
        $this->appointmentTechnicians->removeElement($technician);

        return $this;
    }

    /** @return Collection<int, AppointmentVolunteer> */
    public function getAppointmentVolunteers(): Collection
    {
        return $this->appointmentVolunteers;
    }

    public function addAppointmentVolunteer(AppointmentVolunteer $volunteer): static
    {
        if (!$this->appointmentVolunteers->contains($volunteer)) {
            $this->appointmentVolunteers->add($volunteer);
            $volunteer->setAppointment($this);
        }

        return $this;
    }

    public function removeAppointmentVolunteer(AppointmentVolunteer $volunteer): static
    {
        $this->appointmentVolunteers->removeElement($volunteer);

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

    /** Whether this appointment is part of a series (as parent or child). */
    public function isInSeries(): bool
    {
        return $this->parentAppointment !== null || $this->recurrenceFrequency !== null;
    }

    public function getSeriesRoot(): self
    {
        return $this->parentAppointment ?? $this;
    }

    public function isPast(?\DateTimeInterface $now = null): bool
    {
        return $this->endDate !== null && $this->endDate < ($now ?? new \DateTimeImmutable());
    }
}
