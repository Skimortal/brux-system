<?php

namespace App\Enum;

/**
 * Derived status of a key; it is computed from its loans and the lost flag.
 */
enum KeyStatus: string
{
    case AVAILABLE = 'available';
    case BORROWED = 'borrowed';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Verfügbar',
            self::BORROWED => 'Verliehen',
            self::LOST => 'Verloren',
        };
    }
}
