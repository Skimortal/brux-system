<?php

namespace App\Enum;

enum EventStatus: string
{
    case ACTIVE = 'aktiv';
    case INACTIVE = 'inaktiv';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'event.status.active',
            self::INACTIVE => 'event.status.inactive',
        };
    }
}
