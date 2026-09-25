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
}
