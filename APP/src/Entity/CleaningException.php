<?php

namespace App\Entity;

use App\Repository\CleaningExceptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cancellation of a single occurrence of a cleaning schedule.
 * Extra cleanings are regular appointments of type "cleaning".
 */
#[ORM\Entity(repositoryClass: CleaningExceptionRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_cleaning_exception_schedule_date', columns: ['schedule_id', 'date'])]
class CleaningException
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CleaningSchedule::class, inversedBy: 'exceptions')]
    #[ORM\JoinColumn(name: 'schedule_id', nullable: false, onDelete: 'CASCADE')]
    private ?CleaningSchedule $schedule = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $date = null;

    public function __construct(?CleaningSchedule $schedule = null, ?\DateTimeImmutable $date = null)
    {
        $this->schedule = $schedule;
        $this->date = $date?->setTime(0, 0);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchedule(): ?CleaningSchedule
    {
        return $this->schedule;
    }

    public function setSchedule(?CleaningSchedule $schedule): static
    {
        $this->schedule = $schedule;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date->setTime(0, 0);

        return $this;
    }
}
