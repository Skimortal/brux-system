<?php

namespace App\Entity;

use App\Repository\CleaningExceptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CleaningExceptionRepository::class)]
class CleaningException
{
    public const TYPE_CANCEL = 'cancel';
    public const TYPE_EXTRA = 'extra';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Appointment::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Appointment $appointment = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contact $cleaningContact = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $type = self::TYPE_CANCEL;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeFrom = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $timeTo = null;

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

    public function getCleaningContact(): ?Contact
    {
        return $this->cleaningContact;
    }

    public function setCleaningContact(?Contact $cleaningContact): void
    {
        $this->cleaningContact = $cleaningContact;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
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
}
