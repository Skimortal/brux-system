<?php

namespace App\Entity;

use App\Repository\CleaningScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Recurring cleaning: a cleaning contact comes on fixed weekdays at fixed times.
 */
#[ORM\Entity(repositoryClass: CleaningScheduleRepository::class)]
class CleaningSchedule
{
    public const WEEKDAYS = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Bitte eine Reinigungskraft wählen.')]
    private ?Contact $cleaningContact = null;

    /** @var int[] ISO weekdays, 1 = Monday */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\Count(min: 1, minMessage: 'Bitte mindestens einen Wochentag wählen.')]
    private array $weekdays = [];

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: 'Bitte eine Uhrzeit angeben.')]
    private ?\DateTimeInterface $timeFrom = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: 'Bitte eine Uhrzeit angeben.')]
    private ?\DateTimeInterface $timeTo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $activeFrom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $activeTo = null;

    /** @var Collection<int, CleaningException> */
    #[ORM\OneToMany(targetEntity: CleaningException::class, mappedBy: 'schedule', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $exceptions;

    public function __construct()
    {
        $this->exceptions = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validatePeriods(ExecutionContextInterface $context): void
    {
        if ($this->timeFrom !== null && $this->timeTo !== null && $this->timeTo->format('Hi') <= $this->timeFrom->format('Hi')) {
            $context->buildViolation('Das Ende muss nach dem Beginn liegen.')->atPath('timeTo')->addViolation();
        }
        if ($this->activeFrom !== null && $this->activeTo !== null && $this->activeTo < $this->activeFrom) {
            $context->buildViolation('„Aktiv bis“ liegt vor „Aktiv ab“.')->atPath('activeTo')->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /** @return int[] */
    public function getWeekdays(): array
    {
        return array_map('intval', $this->weekdays);
    }

    /** @param int[] $weekdays */
    public function setWeekdays(array $weekdays): static
    {
        $weekdays = array_values(array_unique(array_map('intval', $weekdays)));
        sort($weekdays);
        $this->weekdays = $weekdays;

        return $this;
    }

    public function getWeekdayLabels(): string
    {
        return implode(', ', array_map(static fn (int $d) => self::WEEKDAYS[$d] ?? '?', $this->getWeekdays()));
    }

    public function getTimeFrom(): ?\DateTimeInterface
    {
        return $this->timeFrom;
    }

    public function setTimeFrom(?\DateTimeInterface $timeFrom): static
    {
        $this->timeFrom = $timeFrom;

        return $this;
    }

    public function getTimeTo(): ?\DateTimeInterface
    {
        return $this->timeTo;
    }

    public function setTimeTo(?\DateTimeInterface $timeTo): static
    {
        $this->timeTo = $timeTo;

        return $this;
    }

    public function getActiveFrom(): ?\DateTimeInterface
    {
        return $this->activeFrom;
    }

    public function setActiveFrom(?\DateTimeInterface $activeFrom): static
    {
        $this->activeFrom = $activeFrom;

        return $this;
    }

    public function getActiveTo(): ?\DateTimeInterface
    {
        return $this->activeTo;
    }

    public function setActiveTo(?\DateTimeInterface $activeTo): static
    {
        $this->activeTo = $activeTo;

        return $this;
    }

    /** @return Collection<int, CleaningException> */
    public function getExceptions(): Collection
    {
        return $this->exceptions;
    }

    public function occursOn(\DateTimeInterface $day): bool
    {
        $date = $day->format('Y-m-d');
        if ($this->activeFrom !== null && $date < $this->activeFrom->format('Y-m-d')) {
            return false;
        }
        if ($this->activeTo !== null && $date > $this->activeTo->format('Y-m-d')) {
            return false;
        }

        return \in_array((int) $day->format('N'), $this->getWeekdays(), true);
    }

    public function getCancellation(\DateTimeInterface $day): ?CleaningException
    {
        $date = $day->format('Y-m-d');
        foreach ($this->exceptions as $exception) {
            if ($exception->getDate()?->format('Y-m-d') === $date) {
                return $exception;
            }
        }

        return null;
    }

    public function isCancelledOn(\DateTimeInterface $day): bool
    {
        return $this->getCancellation($day) !== null;
    }

    public function startOn(\DateTimeInterface $day): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($day)->setTime((int) $this->timeFrom?->format('H'), (int) $this->timeFrom?->format('i'));
    }

    public function endOn(\DateTimeInterface $day): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($day)->setTime((int) $this->timeTo?->format('H'), (int) $this->timeTo?->format('i'));
    }

    public function __toString(): string
    {
        return sprintf('%s · %s', $this->cleaningContact?->getName() ?? 'Reinigung', $this->getWeekdayLabels());
    }
}
