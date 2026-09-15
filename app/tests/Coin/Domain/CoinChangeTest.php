<?php

namespace App\Tests\Coin\Domain;

use App\Coin\Domain\CoinChange;
use App\Coin\Domain\CoinType;
use App\Tests\Coin\Domain\DataProvider\CoinChangeTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class CoinChangeTest extends TestCase
{
    public function testItIsEmptyWithNoUnits(): void
    {
        $change = CoinChange::empty();

        self::assertSame(0, $change->totalInCents());
        self::assertFalse($change->getIterator()->valid());
    }

    public function testItReturnsTheUnitsForTheRequestedCoinType(): void
    {
        $change = CoinChange::fromUnits(['0.10' => 3]);

        self::assertSame(3, $change->unitsFor(CoinType::TenCents));
    }

    public function testItReturnsZeroForCoinTypesNotInTheChange(): void
    {
        $change = CoinChange::fromUnits(['0.10' => 3]);

        self::assertSame(0, $change->unitsFor(CoinType::OneEuro));
    }

    #[DataProviderExternal(CoinChangeTestDataProvider::class, 'unitsFor')]
    public function testItProvidesUnitsForEachCoinType(array $units, CoinType $type, int $expected): void
    {
        $change = CoinChange::fromUnits($units);

        self::assertSame($expected, $change->unitsFor($type));
    }

    #[DataProviderExternal(CoinChangeTestDataProvider::class, 'totalInCents')]
    public function testItComputesTheTotalInCents(array $units, int $expected): void
    {
        $change = CoinChange::fromUnits($units);

        self::assertSame($expected, $change->totalInCents());
    }

    public function testItIsIterableByCoinTypeValue(): void
    {
        $change = CoinChange::fromUnits(['1.00' => 1, '0.05' => 2]);

        self::assertSame(
            ['1.00' => 1, '0.05' => 2],
            iterator_to_array($change)
        );
    }

}
