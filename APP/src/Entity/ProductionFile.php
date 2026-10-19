<?php

namespace App\Entity;

use App\Repository\ProductionFileRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A document for the technicians, e.g. a technical rider or a cue list. Stored outside the
 * public directory and only handed out to people who are logged in.
 */
#[ORM\Entity(repositoryClass: ProductionFileRepository::class)]
class ProductionFile extends Base
{
    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Production $production = null;

    /** Name as uploaded, shown and offered on download. */
    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    /** Name on disk, random so it cannot be guessed. */
    #[ORM\Column(length: 100, unique: true)]
    private ?string $storedName = null;

    #[ORM\Column(length: 100)]
    private string $mimeType = 'application/octet-stream';

    #[ORM\Column]
    private int $size = 0;

    public function getProduction(): ?Production
    {
        return $this->production;
    }

    public function setProduction(Production $production): static
    {
        $this->production = $production;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getStoredName(): ?string
    {
        return $this->storedName;
    }

    public function setStoredName(string $storedName): static
    {
        $this->storedName = $storedName;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSizeLabel(): string
    {
        return $this->size >= 1048576
            ? number_format($this->size / 1048576, 1, ',', '.') . ' MB'
            : max(1, (int) round($this->size / 1024)) . ' KB';
    }
}
