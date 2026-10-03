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

    public function label(): string
    {
        return match ($this) {
            self::PRIVATE => 'Privat / Vermietung',
            self::PRODUCTION => 'Produktionstermin',
            self::CLOSED_EVENT => 'Geschlossene Veranstaltung',
            self::SCHOOL_EVENT => 'Schulveranstaltung',
            self::INTERNAL => 'Intern',
            self::CLEANING => 'Reinigung',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::PRIVATE => 'Privat',
            self::PRODUCTION => 'Produktion',
            self::CLOSED_EVENT => 'Geschl. Veranst.',
            self::SCHOOL_EVENT => 'Schule',
            self::INTERNAL => 'Intern',
            self::CLEANING => 'Reinigung',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PRIVATE => 'Titel, Raum, Zeit',
            self::PRODUCTION => 'Probe oder Aufbau/Abbau einer Produktion, keine Vorstellung',
            self::CLOSED_EVENT => 'mit Technik und Freiwilligen',
            self::SCHOOL_EVENT => 'mit Technik und Freiwilligen',
            self::INTERNAL => 'Besprechung, Blocker',
            self::CLEANING => 'Extra-Reinigung mit Bereichen',
        };
    }

    /** Types that can have technicians and volunteers assigned. */
    public function hasAssignments(): bool
    {
        return \in_array($this, [self::PRODUCTION, self::CLOSED_EVENT, self::SCHOOL_EVENT, self::INTERNAL], true);
    }

    /** Types whose title is derived instead of entered. */
    public function hasDerivedTitle(): bool
    {
        return $this === self::PRODUCTION || $this === self::CLEANING;
    }
}
