<?php

namespace App\Service;

use App\Entity\Production;
use App\Entity\ProductionEvent;

/**
 * Knows which fields WordPress owns while it is the leading system.
 */
final class FieldOwnership
{
    /**
     * @param array<string, string[]> $ownedFields
     */
    public function __construct(private readonly array $ownedFields)
    {
    }

    public function isWordPressField(string $entity, string $field): bool
    {
        return \in_array($field, $this->ownedFields[$entity] ?? [], true);
    }

    /** Whether a field of this record may not be edited here. */
    public function isLocked(Production|ProductionEvent $record, string $field): bool
    {
        [$entity, $fromWordPress] = $record instanceof Production
            ? ['production', $record->isFromWordPress()]
            : ['production_event', $record->getProduction()?->isFromWordPress() ?? false];

        return $fromWordPress && $this->isWordPressField($entity, $field);
    }

    /** Whether new performances can be created here (only when this system owns their dates). */
    public function canCreatePerformances(): bool
    {
        return !$this->isWordPressField('production_event', 'date');
    }
}
