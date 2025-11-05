<?php

namespace App\Enum;

enum ProductionType: string
{
    case INDIVIDUAL = 'individual';
    case GROUP = 'group';

    public function getLabel(): string
    {
        return match($this) {
            self::INDIVIDUAL => 'production.type.individual',
            self::GROUP => 'production.type.group',
        };
    }
}
