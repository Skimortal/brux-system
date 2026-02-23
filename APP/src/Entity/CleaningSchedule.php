<?php


namespace App\Entity;

use App\Repository\CleaningScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CleaningScheduleRepository::class)]
class CleaningSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contact $cleaningContact = null;

    #[ORM\Column(type: Types::JSON)]
    private array $weekdays = [];

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $timeFrom = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $timeTo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $activeFrom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $activeTo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCleaningContact(): ?Contact
    {
        return $this->cleaningContact;
    }

    public function setCleaningContact(?Contact $cleaning): static
    {
        $this->cleaningContact = $cleaning;
        return $this;
    }

    public function getWeekdays(): array
    {
        return $this->weekdays;
    }

    public function setWeekdays(array $weekdays): static
    {
        $this->weekdays = $weekdays;
        return $this;
    }

    public function getTimeFrom(): ?\DateTimeInterface
    {
        return $this->timeFrom;
    }

    public function setTimeFrom(\DateTimeInterface $timeFrom): static
    {
        $this->timeFrom = $timeFrom;
        return $this;
    }

    public function getTimeTo(): ?\DateTimeInterface
    {
        return $this->timeTo;
    }

    public function setTimeTo(\DateTimeInterface $timeTo): static
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
}
