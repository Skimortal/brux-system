<?php

namespace App\Calendar;

/**
 * School holidays in Tyrol by the rules of the Schulzeitgesetz, checked against the
 * ministry's dates for 2026/27. The semester holidays are left out: Tyrol moved them in
 * 2027, so the office enters them itself.
 */
final class SchoolHolidays
{
    /**
     * Holidays overlapping the range.
     *
     * @return list<array{key: string, start: \DateTimeImmutable, end: \DateTimeImmutable, name: string}> end exclusive, by start
     */
    public static function between(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $holidays = [];
        // the Christmas holidays of the year before reach into January
        for ($year = (int) $from->format('Y') - 1; $year <= (int) $to->format('Y'); ++$year) {
            foreach (self::ofYear($year) as $holiday) {
                if ($holiday['start'] < $to && $holiday['end'] > $from) {
                    $holidays[] = $holiday;
                }
            }
        }

        return $holidays;
    }

    /**
     * Holidays starting in the given year.
     *
     * @return list<array{key: string, start: \DateTimeImmutable, end: \DateTimeImmutable, name: string}> end exclusive, by start
     */
    public static function ofYear(int $year): array
    {
        $tz = new \DateTimeZone('Europe/Vienna');
        $date = static fn (int $y, int $month, int $day) => (new \DateTimeImmutable('now', $tz))->setDate($y, $month, $day)->setTime(0, 0);
        $easter = PublicHolidays::easterSunday($year, $tz);
        // summer: from the Saturday between 5 and 11 July until school starts on the second Monday of September
        $summerStart = $date($year, 7, 5)->modify('-1 day')->modify('next saturday');
        $schoolStart = $date($year, 9, 1)->modify('-1 day')->modify('next monday')->modify('+1 week');

        $holidays = [
            ['ostern', $easter->modify('-8 days'), $easter->modify('+2 days'), 'Osterferien'],
            ['pfingsten', $easter->modify('+48 days'), $easter->modify('+51 days'), 'Pfingstferien'],
            ['sommer', $summerStart, $schoolStart, 'Sommerferien'],
            ['herbst', $date($year, 10, 27), $date($year, 11, 1), 'Herbstferien'],
            ['weihnachten', $date($year, 12, 24), $date($year + 1, 1, 7), 'Weihnachtsferien'],
        ];

        return array_map(static fn (array $h) => ['key' => $h[0] . '-' . $year, 'start' => $h[1], 'end' => $h[2], 'name' => $h[3] . ' Tirol'], $holidays);
    }

    /** The holidays with the given key, e.g. "sommer-2027". */
    public static function find(string $key): ?array
    {
        $year = (int) substr($key, strrpos($key, '-') + 1);
        foreach ($year > 0 ? self::ofYear($year) : [] as $holiday) {
            if ($holiday['key'] === $key) {
                return $holiday;
            }
        }

        return null;
    }
}
