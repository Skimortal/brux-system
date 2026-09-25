<?php

namespace App\Enum;

enum AppointmentStatusEnum: string
{
    case RESERVED = 'reserved';
    case CONFIRMED = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::RESERVED => 'Reserviert',
            self::CONFIRMED => 'Fix',
        };
    }
}
