<?php

namespace App\Tests\MachineStatus\Domain;

use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceCannotBeNegative;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\Tests\MachineStatus\Domain\DataProvider\MachineStatusTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class MachineStatusBalanceTest extends TestCase
{
    public function testItInitializesWithZero(): void
    {
        self::assertSame(0.0, MachineStatusBalance::initialize()->value());
    }

    #[DataProviderExternal(MachineStatusTestDataProvider::class, 'validBalances')]
    public function testItCreatesANonNegativeBalance(float $value): void
    {
        $balance = MachineStatusBalance::create($value);

        self::assertSame($value, $balance->value());
        self::assertFalse($balance->isNegativeValue());
    }

    #[DataProviderExternal(MachineStatusTestDataProvider::class, 'negativeBalances')]
    public function testItRejectsNegativeBalances(float $value): void
    {
        $this->expectException(MachineStatusBalanceCannotBeNegative::class);

        MachineStatusBalance::create($value);
    }

    public function testItAddsRoundedToCentsImmutably(): void
    {
        $balance = MachineStatusBalance::create(0.05);

        $result = $balance->add(MachineStatusBalance::create(0.10));

        self::assertSame(0.15, $result->value());
        self::assertSame(0.05, $balance->value());
    }
}
