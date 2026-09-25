<?php

namespace App\Entity;

use App\Repository\KeyLoanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * A key handed to a contact or user for a period of time.
 */
#[ORM\Entity(repositoryClass: KeyLoanRepository::class)]
#[ORM\Index(columns: ['start_at'])]
class KeyLoan extends Base
{
    #[ORM\ManyToOne(targetEntity: KeyManagement::class, inversedBy: 'loans')]
    #[ORM\JoinColumn(name: 'key_id', nullable: false, onDelete: 'CASCADE')]
    private ?KeyManagement $key = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $user = null;

    /** Free-text holder for people that are not in the system (e.g. migrated legacy data). */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $holderName = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'Bitte ein Ausgabedatum angeben.')]
    private ?\DateTimeImmutable $startAt = null;

    /** Planned return. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dueAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne(targetEntity: Appointment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Appointment $appointment = null;

    #[ORM\ManyToOne(targetEntity: ProductionEvent::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?ProductionEvent $productionEvent = null;

    #[Assert\Callback]
    public function validateHolderAndDates(ExecutionContextInterface $context): void
    {
        if ($this->contact === null && $this->user === null && ($this->holderName === null || trim($this->holderName) === '')) {
            $context->buildViolation('Bitte angeben, an wen der Schlüssel geht.')->atPath('contact')->addViolation();
        }
        if ($this->startAt !== null && $this->dueAt !== null && $this->dueAt < $this->startAt) {
            $context->buildViolation('Die Rückgabe liegt vor der Ausgabe.')->atPath('dueAt')->addViolation();
        }
    }

    public function getKey(): ?KeyManagement
    {
        return $this->key;
    }

    public function setKey(?KeyManagement $key): static
    {
        $this->key = $key;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getHolderName(): ?string
    {
        return $this->holderName;
    }

    public function setHolderName(?string $holderName): static
    {
        $this->holderName = $holderName;

        return $this;
    }

    public function getHolderLabel(): string
    {
        return $this->contact?->getName()
            ?? $this->user?->getFullname()
            ?? $this->holderName
            ?? 'Unbekannt';
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getDueAt(): ?\DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?\DateTimeImmutable $returnedAt): static
    {
        $this->returnedAt = $returnedAt;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
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

    public function getProductionEvent(): ?ProductionEvent
    {
        return $this->productionEvent;
    }

    public function setProductionEvent(?ProductionEvent $productionEvent): static
    {
        $this->productionEvent = $productionEvent;

        return $this;
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isActiveAt(\DateTimeInterface $now): bool
    {
        return !$this->isReturned() && $this->startAt !== null && $this->startAt <= $now;
    }

    public function isPlannedAt(\DateTimeInterface $now): bool
    {
        return !$this->isReturned() && $this->startAt !== null && $this->startAt > $now;
    }

    public function isOverdue(?\DateTimeInterface $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        return $this->isActiveAt($now) && $this->dueAt !== null && $this->dueAt < $now;
    }

    /** End used for calendar display: returned, planned return or open-ended. */
    public function getDisplayEnd(): ?\DateTimeImmutable
    {
        return $this->returnedAt ?? $this->dueAt;
    }
}
