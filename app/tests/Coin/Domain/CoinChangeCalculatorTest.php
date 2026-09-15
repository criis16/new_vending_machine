<?php

namespace App\Tests\Coin\Domain;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinChangeCalculator;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinValue;
use App\Coin\Domain\Exceptions\NotEnoughCoinsToReturn;
use App\Tests\Coin\Domain\DataProvider\CoinChangeCalculatorTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class CoinChangeCalculatorTest extends TestCase
{
    #[DataProviderExternal(CoinChangeCalculatorTestDataProvider::class, 'exactChange')]
    public function testItReturnsTheExactCombination(
        float $balance,
        array $coins,
        array $expectedUnits
    ): void
    {
        $change = new CoinChangeCalculator()->calculate($balance, $coins);

        self::assertSame((int)round($balance * 100), $change->totalInCents());
        self::assertSame($expectedUnits, iterator_to_array($change));
    }

    public function testItThrowsWhenThereIsNotEnoughStockToMatchTheBalance(): void
    {
        $coins = [Coin::create(
            CoinId::generate(),
            CoinValue::create(0.10),
            CoinQuantity::create(5)
        )];

        $this->expectException(NotEnoughCoinsToReturn::class);

        new CoinChangeCalculator()->calculate(1.00, $coins);
    }

    public function testItThrowsWhenCoinsCannotCombineToTheExactBalance(): void
    {
        $coins = [Coin::create(
            CoinId::generate(),
            CoinValue::create(0.10),
            CoinQuantity::create(2)
        )];

        $this->expectException(NotEnoughCoinsToReturn::class);

        new CoinChangeCalculator()->calculate(0.30, $coins);
    }

    public function testItThrowsWhenNoCoinsAreAvailable(): void
    {
        $this->expectException(NotEnoughCoinsToReturn::class);

        new CoinChangeCalculator()->calculate(0.05, []);
    }
}
