<?php

namespace App\Enum;

enum AppointmentTypeEnum: string
{
    case PRIVATE = 'private';
    case PRODUCTION = 'production';
    case CLOSED_EVENT = 'closed_event';
    case SCHOOL_EVENT = 'school_event';
    case INTERNAL = 'internal';
    case CLEANING = 'cleaning';

    public function getLabel(): string
    {
        return match($this) {
            self::PRIVATE => 'appointment.type.private',
            self::PRODUCTION => 'appointment.type.production',
            self::CLOSED_EVENT => 'appointment.type.closed_event',
            self::SCHOOL_EVENT => 'appointment.type.school_event',
            self::INTERNAL => 'appointment.type.internal',
            self::CLEANING => 'appointment.type.cleaning',
        };
    }
}
