<?php

namespace App\Enum;

enum EventReservationStatus: string
{
    case ACTIVE = 'aktiv';
    case INACTIVE = 'inaktiv';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'event.reservation_status.active',
            self::INACTIVE => 'event.reservation_status.inactive',
        };
    }
}
