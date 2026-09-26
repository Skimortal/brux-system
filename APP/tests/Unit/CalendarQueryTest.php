<?php

namespace App\Tests\Unit;

use App\Calendar\CalendarQuery;
use App\Calendar\EntryGroup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CalendarQueryTest extends TestCase
{
    public function testWeekStartsOnMonday(): void
    {
        $query = new CalendarQuery(new \DateTimeImmutable('2026-10-15'), 'week');

        self::assertSame('2026-10-12', $query->rangeStart()->format('Y-m-d'));
        self::assertSame('2026-10-19', $query->rangeEnd()->format('Y-m-d'));
    }

    public function testMonthCoversFullWeeks(): void
    {
        $query = new CalendarQuery(new \DateTimeImmutable('2026-11-18'), 'month');

        self::assertSame('2026-10-26', $query->rangeStart()->format('Y-m-d'));
        self::assertSame('2026-12-07', $query->rangeEnd()->format('Y-m-d'));
        self::assertSame('2026-10-01', $query->previousDate()->format('Y-m-d'));
    }

    public function testFromRequestFallsBackToDefaults(): void
    {
        $query = CalendarQuery::fromRequest(new Request(['view' => 'year', 'hide' => 'keys,unknown', 'date' => 'garbage']), new \DateTimeImmutable('2026-01-05'));

        self::assertSame('month', $query->view);
        self::assertSame('2026-01-05', $query->date->format('Y-m-d'));
        self::assertTrue($query->isHidden(EntryGroup::KEYS));
        self::assertFalse($query->isHidden(EntryGroup::CLEANING));
    }

    public function testParamsOmitDefaultsAndToggleFilters(): void
    {
        $query = new CalendarQuery(new \DateTimeImmutable('2026-10-15'), 'month', panel: 'pe-4');

        self::assertSame(['date' => '2026-10-15', 'panel' => 'pe-4'], $query->params());
        self::assertSame(['date' => '2026-10-15'], $query->params(['panel' => null]));
        self::assertSame('cleaning', $query->toggleParams(EntryGroup::CLEANING)['hide']);
    }
}
