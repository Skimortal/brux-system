<?php

namespace App\Enum;

/**
 * What a line of the daily digest mail is about.
 */
enum DigestKind: string
{
    // a technician is assigned and asked to answer
    case ASSIGNED = 'assigned';
    // a technician's assignment is confirmed
    case CONFIRMED = 'confirmed';
    // an appointment is sent out to all technicians
    case OPEN_CALL = 'open_call';
    // a volunteer is assigned
    case VOLUNTEER = 'volunteer';
}
