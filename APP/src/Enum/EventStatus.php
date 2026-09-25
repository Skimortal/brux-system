<?php

namespace App\Enum;

enum EventStatus: string
{
    case ACTIVE = 'aktiv';
    case INACTIVE = 'inaktiv';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktiv',
            self::INACTIVE => 'Inaktiv',
        };
    }
}
