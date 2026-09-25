<?php

namespace App\Enum;

enum EventReservationStatus: string
{
    case ACTIVE = 'aktiv';
    case INACTIVE = 'inaktiv';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Online-Reservierung aktiv',
            self::INACTIVE => 'Online-Reservierung inaktiv',
        };
    }
}
