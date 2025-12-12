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

    public function getLabel(): string
    {
        return match($this) {
            self::BAR => 'volunteer.task.bar',
            self::SETUP => 'volunteer.task.setup',
            self::TEARDOWN => 'volunteer.task.teardown',
            self::TICKETING => 'volunteer.task.ticketing',
            self::CLEANUP => 'volunteer.task.cleanup',
            self::OTHER => 'volunteer.task.other',
        };
    }
}
