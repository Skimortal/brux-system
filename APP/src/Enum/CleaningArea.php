<?php

namespace App\Enum;

/**
 * Areas that can be selected for an extra cleaning appointment.
 */
enum CleaningArea: string
{
    case DAILY = 'daily';
    case BLACK = 'black';
    case WHITE = 'white';
    case WARDROBE = 'wardrobe';
    case TOILET = 'toilet';
    case OFFICE = 'office';

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Tägliche Reinigung',
            self::BLACK => 'Raum Schwarz',
            self::WHITE => 'Raum Weiss',
            self::WARDROBE => 'Künstlerinnengarderobe',
            self::TOILET => 'Publikumstoilette',
            self::OFFICE => 'Büro',
        };
    }
}
