<?php

namespace App\Tests\Unit;

use App\Calendar\SchoolHolidays;
use PHPUnit\Framework\TestCase;

final class SchoolHolidaysTest extends TestCase
{
    public function testSchoolYear2026To2027AsPublishedByTheMinistry(): void
    {
        $tz = new \DateTimeZone('Europe/Vienna');
        $holidays = [];
        foreach (SchoolHolidays::between(new \DateTimeImmutable('2026-09-14', $tz), new \DateTimeImmutable('2027-09-13', $tz)) as $h) {
            $holidays[$h['name']] = $h['start']->format('Y-m-d') . '/' . $h['end']->modify('-1 day')->format('Y-m-d');
        }

        self::assertSame([
            'Herbstferien Tirol' => '2026-10-27/2026-10-31',
            'Weihnachtsferien Tirol' => '2026-12-24/2027-01-06',
            'Osterferien Tirol' => '2027-03-20/2027-03-29',
            'Pfingstferien Tirol' => '2027-05-15/2027-05-17',
            'Sommerferien Tirol' => '2027-07-10/2027-09-12',
        ], $holidays);
    }

    public function testSummerEndsTheDayBeforeTheSecondMondayOfSeptember(): void
    {
        self::assertSame('2026-09-14', SchoolHolidays::find('sommer-2026')['end']->format('Y-m-d'));
        self::assertSame('2026-07-11', SchoolHolidays::find('sommer-2026')['start']->format('Y-m-d'));
        self::assertNull(SchoolHolidays::find('semester-2027'));
    }
}
