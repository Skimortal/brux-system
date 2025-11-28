<?php

namespace App\Service;

use App\Entity\Production;

class CalendarColorService
{
    // Fixe Farben für Termintypen
    private const COLOR_PRIVATE = '#9e9e9e';      // Grau
    private const COLOR_TECHNICIAN = '#2196F3';   // Blau
    private const COLOR_CLEANING = '#4CAF50';     // Grün
    private const COLOR_PRODUCTION = '#FF9800';   // Orange

    // Basis-Farben für Produktions-Events (werden je nach Production variiert)
    private const PRODUCTION_EVENT_BASE_COLORS = [
        '#E91E63', // Pink
        '#9C27B0', // Violett
        '#673AB7', // Dunkel-Violett
        '#3F51B5', // Indigo
        '#00BCD4', // Cyan
        '#009688', // Teal
        '#FF5722', // Deep Orange
        '#795548', // Braun
        '#607D8B', // Blue Grey
    ];

    /**
     * Gibt die fixe Farbe für einen Appointment-Typ zurück
     */
    public function getAppointmentColor(
        ?\App\Entity\Cleaning $cleaning = null,
        ?\App\Entity\Technician $technician = null,
        ?\App\Entity\Production $production = null
    ): string {
        if ($cleaning) {
            return self::COLOR_CLEANING;
        }
        if ($technician) {
            return self::COLOR_TECHNICIAN;
        }
        if ($production) {
            return self::COLOR_PRODUCTION;
        }
        return self::COLOR_PRIVATE;
    }

    /**
     * Gibt eine eindeutige Farbe für ein Produktions-Event zurück
     * Basiert auf der Production-ID mit Nuancen für verschiedene Produktionen
     */
    public function getProductionEventColor(int $productionId): string {
        $baseColorIndex = $productionId % count(self::PRODUCTION_EVENT_BASE_COLORS);
        return self::PRODUCTION_EVENT_BASE_COLORS[$baseColorIndex];
    }

    /**
     * Gibt eine ähnliche Farbe (Nuance) für eine Produktion im Appointment-Kontext zurück
     * Wird heller gemacht als das Event
     */
    public function getProductionAppointmentNuance(int $productionId): string {
        $baseColor = $this->getProductionEventColor($productionId);
        // Konvertiere HEX zu RGB und mache es heller
        return $this->lightenColor($baseColor, 20);
    }

    /**
     * Hellere Nuance einer Farbe erstellen
     */
    private function lightenColor(string $hexColor, int $percent): string {
        $hex = str_replace('#', '', $hexColor);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = min(255, $r + (255 - $r) * $percent / 100);
        $g = min(255, $g + (255 - $g) * $percent / 100);
        $b = min(255, $b + (255 - $b) * $percent / 100);

        return sprintf('#%02X%02X%02X', (int)$r, (int)$g, (int)$b);
    }
}
