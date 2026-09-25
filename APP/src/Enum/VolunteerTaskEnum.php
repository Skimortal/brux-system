<?php

namespace App\Enum;

enum VolunteerTaskEnum: string
{
    case BAR = 'bar';
    case SETUP = 'setup';
    case TEARDOWN = 'teardown';
    case TICKETING = 'ticketing';
    case CLEANUP = 'cleanup';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BAR => 'Bar',
            self::SETUP => 'Aufbau',
            self::TEARDOWN => 'Abbau',
            self::TICKETING => 'Ticketing',
            self::CLEANUP => 'Aufräumen',
            self::OTHER => 'Anderes',
        };
    }
}
