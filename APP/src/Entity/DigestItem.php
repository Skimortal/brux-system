<?php

namespace App\Entity;

use App\Enum\DigestKind;
use App\Repository\DigestItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * News for one person, held back for the daily digest mail. Written with plain SQL by
 * TechnicianDigest (it is queued while Doctrine flushes), read and removed when sent.
 */
#[ORM\Entity(repositoryClass: DigestItemRepository::class)]
#[ORM\Index(columns: ['contact_id', 'created_at'])]
class DigestItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Appointment $appointment = null;

    #[ORM\Column(length: 20, enumType: DigestKind::class)]
    private DigestKind $kind;

    // further dates, e.g. of a series
    #[ORM\Column]
    private int $more = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function getKind(): DigestKind
    {
        return $this->kind;
    }

    public function getMore(): int
    {
        return $this->more;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
