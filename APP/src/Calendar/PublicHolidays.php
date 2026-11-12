<?php

namespace App\Calendar;

/**
 * The 13 public holidays of the Arbeitsruhegesetz (§ 7 Abs. 2), the school-free days of Tyrol
 * (§ 9 T-LSchG: St. Joseph's day, All Souls' day) and a few days people expect to see.
 * Movable feasts are counted from Easter Sunday.
 */
final class PublicHolidays
{
    /**
     * @return list<array{date: \DateTimeImmutable, name: string, note: ?string}> by date
     */
    public static function between(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $holidays = [];
        for ($year = (int) $from->format('Y'); $year <= (int) $to->format('Y'); ++$year) {
            foreach (self::ofYear($year) as $holiday) {
                if ($holiday['date'] >= $from->setTime(0, 0) && $holiday['date'] < $to) {
                    $holidays[] = $holiday;
                }
            }
        }

        return $holidays;
    }

    /**
     * @return list<array{date: \DateTimeImmutable, name: string, note: ?string}> by date
     */
    public static function ofYear(int $year): array
    {
        $tz = new \DateTimeZone('Europe/Vienna');
        $easter = self::easterSunday($year, $tz);
        $fixed = static fn (int $month, int $day) => (new \DateTimeImmutable('now', $tz))->setDate($year, $month, $day)->setTime(0, 0);
        $movable = static fn (int $days) => $easter->modify(sprintf('%+d days', $days));

        $holidays = [
            [$fixed(1, 1), 'Neujahr', null],
            [$fixed(1, 6), 'Heilige Drei Könige', null],
            [$fixed(3, 19), 'Josefitag', 'Landespatron Tirol, schulfrei, kein gesetzlicher Feiertag'],
            [$movable(0), 'Ostersonntag', 'Sonntag, kein eigener gesetzlicher Feiertag'],
            [$movable(1), 'Ostermontag', null],
            [$fixed(5, 1), 'Staatsfeiertag', null],
            [$movable(39), 'Christi Himmelfahrt', null],
            [$movable(49), 'Pfingstsonntag', 'Sonntag, kein eigener gesetzlicher Feiertag'],
            [$movable(50), 'Pfingstmontag', null],
            [$movable(60), 'Fronleichnam', null],
            [$fixed(8, 15), 'Mariä Himmelfahrt', null],
            [$fixed(10, 26), 'Nationalfeiertag', null],
            [$fixed(11, 1), 'Allerheiligen', null],
            [$fixed(11, 2), 'Allerseelen', 'in Tirol schulfrei, kein gesetzlicher Feiertag'],
            [$fixed(12, 8), 'Mariä Empfängnis', null],
            [$fixed(12, 24), 'Heiliger Abend', 'kein gesetzlicher Feiertag'],
            [$fixed(12, 25), 'Christtag', null],
            [$fixed(12, 26), 'Stefanitag', null],
            [$fixed(12, 31), 'Silvester', 'kein gesetzlicher Feiertag'],
        ];
        usort($holidays, static fn (array $a, array $b) => $a[0] <=> $b[0]);

        return array_map(static fn (array $h) => ['date' => $h[0], 'name' => $h[1], 'note' => $h[2]], $holidays);
    }

    /** The holiday on the given day, if any. */
    public static function on(\DateTimeImmutable $day): ?array
    {
        foreach (self::ofYear((int) $day->format('Y')) as $holiday) {
            if ($holiday['date']->format('Y-m-d') === $day->format('Y-m-d')) {
                return $holiday;
            }
        }

        return null;
    }

    /** Gregorian Easter Sunday (anonymous Gregorian algorithm), without the calendar extension. */
    public static function easterSunday(int $year, ?\DateTimeZone $tz = null): \DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return (new \DateTimeImmutable('now', $tz ?? new \DateTimeZone('Europe/Vienna')))->setDate($year, $month, $day)->setTime(0, 0);
    }
}
