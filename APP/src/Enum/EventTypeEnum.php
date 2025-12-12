<?php

namespace App\Enum;

enum EventTypeEnum: string
{
    case REHEARSAL = 'rehearsal';
    case SETUP_TEARDOWN = 'setup_teardown';
    case EVENT = 'event';

    public function getLabel(): string
    {
        return match($this) {
            self::REHEARSAL => 'appointment.event_type.rehearsal',
            self::SETUP_TEARDOWN => 'appointment.event_type.setup_teardown',
            self::EVENT => 'appointment.event_type.event',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::REHEARSAL => 'ti-music-alt',
            self::SETUP_TEARDOWN => 'ti-package',
            self::EVENT => 'ti-flag-alt',
        };
    }
}
