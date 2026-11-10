<?php

namespace App\Tests\Unit;

use App\Entity\Appointment;
use App\Service\AppSettings;
use App\Service\TechnicianDigest;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\MessageBusInterface;

final class TechnicianDigestTest extends TestCase
{
    private function digest(string $now, bool $enabled = true, int $hour = 17): TechnicianDigest
    {
        $settings = $this->createMock(AppSettings::class);
        $settings->method('digestEnabled')->willReturn($enabled);
        $settings->method('digestHour')->willReturn($hour);

        return new TechnicianDigest(
            $this->createMock(Connection::class),
            $this->createMock(MessageBusInterface::class),
            $settings,
            new MockClock(new \DateTimeImmutable($now, new \DateTimeZone('Europe/Vienna'))),
        );
    }

    private function appointmentAt(string $start): Appointment
    {
        return (new Appointment())->setStartDate(new \DateTime($start, new \DateTimeZone('Europe/Vienna')));
    }

    public function testRunsTodayBeforeTheHourAndTomorrowAfter(): void
    {
        self::assertSame('2026-11-10 17:00', $this->digest('2026-11-10 09:30')->nextRun()->format('Y-m-d H:i'));
        self::assertSame('2026-11-11 17:00', $this->digest('2026-11-10 17:00')->nextRun()->format('Y-m-d H:i'));
        self::assertSame('heute um 17:00 Uhr', $this->digest('2026-11-10 09:30')->nextRunLabel());
        self::assertSame('morgen um 08:00 Uhr', $this->digest('2026-11-10 09:30', hour: 8)->nextRunLabel());
    }

    public function testShortNoticeGoesOutRightAway(): void
    {
        $digest = $this->digest('2026-11-10 09:30');

        self::assertFalse($digest->defers($this->appointmentAt('2026-11-12 09:00')));
        self::assertTrue($digest->defers($this->appointmentAt('2026-11-12 10:00')));
    }

    public function testNothingWaitsWhenSwitchedOff(): void
    {
        self::assertFalse($this->digest('2026-11-10 09:30', enabled: false)->defers($this->appointmentAt('2026-12-01 19:30')));
    }
}
