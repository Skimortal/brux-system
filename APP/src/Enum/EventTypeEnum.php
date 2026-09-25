<?php

namespace App\Enum;

enum EventTypeEnum: string
{
    case REHEARSAL = 'rehearsal';
    case SETUP_TEARDOWN = 'setup_teardown';
    case EVENT = 'event';

    public function label(): string
    {
        return match ($this) {
            self::REHEARSAL => 'Probe',
            self::SETUP_TEARDOWN => 'Aufbau/Abbau',
            self::EVENT => 'Veranstaltung',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::REHEARSAL => 'lucide:music',
            self::SETUP_TEARDOWN => 'lucide:package',
            self::EVENT => 'lucide:flag',
        };
    }
}
