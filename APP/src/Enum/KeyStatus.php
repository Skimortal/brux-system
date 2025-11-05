<?php

namespace App\Enum;

enum KeyStatus: string
{
    case AVAILABLE = 'available';
    case BORROWED = 'borrowed';
    case LOST = 'lost';

    public function getLabel(): string
    {
        return match($this) {
            self::AVAILABLE => 'key_management.status.available',
            self::BORROWED => 'key_management.status.borrowed',
            self::LOST => 'key_management.status.lost',
        };
    }
}
