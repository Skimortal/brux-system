<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Bookkeeping for records that are exchanged with the WordPress site.
 */
trait WordPressSyncTrait
{
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $syncedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastPushAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lastPushError = null;

    public function getSyncedAt(): ?\DateTimeImmutable
    {
        return $this->syncedAt;
    }

    public function setSyncedAt(?\DateTimeImmutable $syncedAt): static
    {
        $this->syncedAt = $syncedAt;

        return $this;
    }

    public function getLastPushAt(): ?\DateTimeImmutable
    {
        return $this->lastPushAt;
    }

    public function getLastPushError(): ?string
    {
        return $this->lastPushError;
    }

    public function markPushed(?string $error): static
    {
        $this->lastPushAt = new \DateTimeImmutable();
        $this->lastPushError = $error;

        return $this;
    }
}
