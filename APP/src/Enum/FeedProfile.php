<?php

namespace App\Enum;

/**
 * What a calendar subscription contains.
 */
enum FeedProfile: string
{
    /** Performances and events, released rehearsals; premieres as a calendar of their own. */
    case BOARD = 'board';
    /** Everything incl. cleaning, one calendar per room. */
    case TECHNICIANS = 'technicians';
    /** Everything per room without the stays, and the stays per room as a calendar of their own. */
    case MANAGEMENT = 'management';
    /** One technician's own assignments. */
    case MINE = 'mine';

    public function label(): string
    {
        return match ($this) {
            self::BOARD => 'Vorstand',
            self::TECHNICIANS => 'Technik',
            self::MANAGEMENT => 'Leitung',
            self::MINE => 'Meine Einsätze',
        };
    }

    /**
     * The calendars of a subscription: part key => label; "rooms" means one per room.
     *
     * @return array<string, array{label: string, rooms: bool}>
     */
    public function parts(): array
    {
        return match ($this) {
            self::BOARD => ['veranstaltungen' => ['label' => 'Veranstaltungen', 'rooms' => false], 'premieren' => ['label' => 'Premieren', 'rooms' => false]],
            self::TECHNICIANS => ['termine' => ['label' => 'Alle Termine', 'rooms' => true]],
            self::MANAGEMENT => ['termine' => ['label' => 'Termine', 'rooms' => true], 'aufenthalte' => ['label' => 'Im Haus', 'rooms' => true]],
            self::MINE => ['einsaetze' => ['label' => 'Meine Einsätze', 'rooms' => false]],
        };
    }
}
