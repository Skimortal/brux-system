<?php

namespace App\Enum;

/**
 * Where a technician's assignment to an appointment stands: we assign, they accept or
 * decline, we confirm. "Interested" comes from a technician answering an open call.
 */
enum TechnicianStatus: string
{
    case INTERESTED = 'interested';
    case ASSIGNED = 'assigned';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case CONFIRMED = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::INTERESTED => 'Interesse',
            self::ASSIGNED => 'eingeteilt',
            self::ACCEPTED => 'zugesagt',
            self::DECLINED => 'abgesagt',
            self::CONFIRMED => 'bestätigt',
        };
    }

    /** Whether the person is (still) planned for the appointment. */
    public function isStaffed(): bool
    {
        return \in_array($this, [self::ASSIGNED, self::ACCEPTED, self::CONFIRMED], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::CONFIRMED => 'badge-ok',
            self::ACCEPTED, self::INTERESTED => 'badge-accent',
            self::DECLINED => 'badge-danger',
            self::ASSIGNED => 'badge-warn',
        };
    }
}
