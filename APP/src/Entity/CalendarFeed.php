<?php

namespace App\Entity;

use App\Enum\FeedProfile;
use App\Repository\CalendarFeedRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A personal calendar subscription (iCal): its secret token is the only key, so it can be
 * renewed (new token) or deleted any time.
 */
#[ORM\Entity(repositoryClass: CalendarFeedRepository::class)]
class CalendarFeed extends Base
{
    /** Who the link is for, e.g. "Vorstand – Maria". */
    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(length: 20, enumType: FeedProfile::class)]
    private FeedProfile $profile = FeedProfile::BOARD;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    /** The technician a "mine" or technician subscription belongs to. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Contact $contact = null;

    public function __construct()
    {
        $this->token = self::newToken();
    }

    public static function newToken(): string
    {
        return bin2hex(random_bytes(20));
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getProfile(): FeedProfile
    {
        return $this->profile;
    }

    public function setProfile(FeedProfile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /** A new secret link; the old one stops working. */
    public function renewToken(): static
    {
        $this->token = self::newToken();

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
}
