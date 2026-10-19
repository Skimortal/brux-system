<?php
namespace App\Enum;

enum EmailTemplateKey: string
{
    case PASSWORD_RESET_REQUEST = 'password_reset_request';
    case TECHNICIAN_INVITATION = 'technician_invitation';
    case TECHNICIAN_ASSIGNED = 'technician_assigned';
    case TECHNICIAN_CONFIRMED = 'technician_confirmed';
    case TECHNICIAN_ANSWERED = 'technician_answered';
    case TECHNICIAN_OPEN_CALL = 'technician_open_call';

    public function label(): string
    {
        return match($this) {
            self::PASSWORD_RESET_REQUEST     => 'Passwort vergessen',
            self::TECHNICIAN_INVITATION      => 'Technik: Einladung zum Zugang',
            self::TECHNICIAN_ASSIGNED        => 'Technik: eingeteilt (bitte zusagen)',
            self::TECHNICIAN_CONFIRMED       => 'Technik: Einsatz bestätigt',
            self::TECHNICIAN_ANSWERED        => 'Technik: Antwort an das Büro',
            self::TECHNICIAN_OPEN_CALL       => 'Technik: offener Termin',
        };
    }

    /** Twig template used while no template of this key has been saved in the administration. */
    public function defaultTemplate(): ?string
    {
        return match($this) {
            self::PASSWORD_RESET_REQUEST => null,
            default => 'email/' . $this->value . '.html.twig',
        };
    }
}
