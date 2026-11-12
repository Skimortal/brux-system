<?php

namespace App\Tests\Unit;

use App\Calendar\PublicHolidays;
use PHPUnit\Framework\TestCase;

final class PublicHolidaysTest extends TestCase
{
    public function testEasterSunday(): void
    {
        self::assertSame('2026-04-05', PublicHolidays::easterSunday(2026)->format('Y-m-d'));
        self::assertSame('2027-03-28', PublicHolidays::easterSunday(2027)->format('Y-m-d'));
        self::assertSame('2025-04-20', PublicHolidays::easterSunday(2025)->format('Y-m-d'));
        self::assertSame('2038-04-25', PublicHolidays::easterSunday(2038)->format('Y-m-d'));
    }

    public function testMovableFeastsOf2026(): void
    {
        $byName = [];
        foreach (PublicHolidays::ofYear(2026) as $holiday) {
            $byName[$holiday['name']] = $holiday['date']->format('Y-m-d');
        }

        self::assertSame('2026-04-06', $byName['Ostermontag']);
        self::assertSame('2026-05-14', $byName['Christi Himmelfahrt']);
        self::assertSame('2026-05-25', $byName['Pfingstmontag']);
        self::assertSame('2026-06-04', $byName['Fronleichnam']);
        self::assertSame('2026-10-26', $byName['Nationalfeiertag']);
    }

    public function testBetweenSpansTheTurnOfTheYear(): void
    {
        $tz = new \DateTimeZone('Europe/Vienna');
        $names = array_map(
            static fn (array $h) => $h['name'],
            PublicHolidays::between(new \DateTimeImmutable('2026-12-24', $tz), new \DateTimeImmutable('2027-01-07', $tz)),
        );

        self::assertSame(['Heiliger Abend', 'Christtag', 'Stefanitag', 'Silvester', 'Neujahr', 'Heilige Drei Könige'], $names);
        self::assertSame('Allerheiligen', PublicHolidays::on(new \DateTimeImmutable('2026-11-01', $tz))['name']);
        self::assertNull(PublicHolidays::on(new \DateTimeImmutable('2026-11-02', $tz)));
    }
}
