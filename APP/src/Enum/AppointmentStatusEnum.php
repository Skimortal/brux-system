<?php

namespace App\Enum;

enum AppointmentStatusEnum: string
{
    case RESERVED = 'reserved';
    case CONFIRMED = 'confirmed';

    public function getLabel(): string
    {
        return match($this) {
            self::RESERVED => 'appointment.status.reserved',
            self::CONFIRMED => 'appointment.status.confirmed',
        };
    }
}
