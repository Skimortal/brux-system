<?php

namespace App\Calendar;

use App\Enum\AppointmentTypeEnum;

/**
 * Filter groups of the calendar legend.
 */
enum EntryGroup: string
{
    case PERFORMANCE = 'performance';
    case PRODUCTION = 'production';
    case EVENT = 'event';
    case PRIVATE = 'private';
    case INTERNAL = 'internal';
    case CLEANING = 'cleaning';
    case KEYS = 'keys';
    case STAY = 'stay';

    public function label(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'Vorstellungen',
            self::PRODUCTION => 'Produktion',
            self::EVENT => 'Veranst. & Schule',
            self::PRIVATE => 'Privat',
            self::INTERNAL => 'Intern',
            self::CLEANING => 'Reinigung',
            self::KEYS => 'Schlüssel',
            self::STAY => 'Im Haus',
        };
    }

    /** CSS type key used for colors. */
    public function colorKey(): string
    {
        return match ($this) {
            self::PERFORMANCE => 'performance',
            self::PRODUCTION => 'production',
            self::EVENT => 'closed_event',
            self::PRIVATE => 'private',
            self::INTERNAL => 'internal',
            self::CLEANING => 'cleaning',
            self::KEYS => 'key',
            self::STAY => 'stay',
        };
    }

    public static function forAppointmentType(AppointmentTypeEnum $type): self
    {
        return match ($type) {
            AppointmentTypeEnum::PRODUCTION => self::PRODUCTION,
            AppointmentTypeEnum::CLOSED_EVENT, AppointmentTypeEnum::SCHOOL_EVENT => self::EVENT,
            AppointmentTypeEnum::PRIVATE => self::PRIVATE,
            AppointmentTypeEnum::INTERNAL => self::INTERNAL,
            AppointmentTypeEnum::CLEANING => self::CLEANING,
        };
    }
}
