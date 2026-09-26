<?php

namespace App\Tests\Unit;

use App\Entity\KeyLoan;
use App\Entity\KeyManagement;
use App\Enum\KeyStatus;
use PHPUnit\Framework\TestCase;

final class KeyLoanTest extends TestCase
{
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new \DateTimeImmutable('2026-10-14 12:00');
    }

    private function loan(string $start, ?string $due = null, ?string $returned = null): KeyLoan
    {
        return (new KeyLoan())
            ->setHolderName('Test')
            ->setStartAt(new \DateTimeImmutable($start))
            ->setDueAt($due ? new \DateTimeImmutable($due) : null)
            ->setReturnedAt($returned ? new \DateTimeImmutable($returned) : null);
    }

    public function testKeyIsAvailableWithoutActiveLoan(): void
    {
        $key = (new KeyManagement())->setName('Foyer');
        $key->addLoan($this->loan('2026-10-01', '2026-10-02', '2026-10-02'));
        $key->addLoan($this->loan('2026-10-20 18:00', '2026-10-20 22:00'));

        self::assertSame(KeyStatus::AVAILABLE, $key->getStatus($this->now));
        self::assertNull($key->getActiveLoan($this->now));
        self::assertSame('2026-10-20', $key->getNextLoan($this->now)?->getStartAt()?->format('Y-m-d'));
    }

    public function testActiveAndOverdueLoan(): void
    {
        $key = (new KeyManagement())->setName('Lager');
        $key->addLoan($this->loan('2026-10-02', '2026-10-09'));

        self::assertSame(KeyStatus::BORROWED, $key->getStatus($this->now));
        self::assertTrue($key->isOverdue($this->now));
    }

    public function testLostWins(): void
    {
        $key = (new KeyManagement())->setName('Hintereingang')->setLost(true);
        $key->addLoan($this->loan('2026-10-10'));

        self::assertSame(KeyStatus::LOST, $key->getStatus($this->now));
    }

    public function testOpenLoanIsNeverOverdue(): void
    {
        self::assertFalse($this->loan('2026-09-01')->isOverdue($this->now));
    }

    public function testShortLoanIsOneBarLongLoanOnlyHandoverAndDue(): void
    {
        $key = (new KeyManagement())->setName('Foyer');
        $short = $this->loan('2026-10-16 17:00', '2026-10-18 23:00');
        $key->addLoan($short);
        $segments = \App\Calendar\CalendarBuilder::loanSegments($short, $this->now);
        self::assertCount(1, $segments);

        $long = $this->loan('2025-03-01', '2027-12-02');
        $key->addLoan($long);
        $segments = \App\Calendar\CalendarBuilder::loanSegments($long, $this->now);
        self::assertCount(2, $segments);
        self::assertSame('2025-03-01', $segments[0]['start']->format('Y-m-d'));
        self::assertSame('2027-12-02', $segments[1]['start']->format('Y-m-d'));
        self::assertStringContainsString('Rückgabe von', $segments[1]['label']);

        $overdue = $this->loan('2026-10-10', '2026-10-12');
        $key->addLoan($overdue);
        $segments = \App\Calendar\CalendarBuilder::loanSegments($overdue, $this->now);
        self::assertSame('overdue', $segments[1]['state']);
    }
}
