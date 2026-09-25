<?php

namespace App\Enum;

/**
 * Functional role of a contact category; replaces hard-coded category ids.
 */
enum ContactRole: string
{
    case TECHNICIAN = 'technician';
    case VOLUNTEER = 'volunteer';
    case CLEANING = 'cleaning';

    public function label(): string
    {
        return match ($this) {
            self::TECHNICIAN => 'Technik',
            self::VOLUNTEER => 'Freiwillige',
            self::CLEANING => 'Reinigung',
        };
    }
}
