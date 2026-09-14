<?php

namespace App\Tests\Coin\Domain;

use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\Exceptions\CoinWithNegativeQuantity;
use App\Tests\Coin\Domain\DataProvider\CoinTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class CoinQuantityTest extends TestCase
{
    #[DataProviderExternal(CoinTestDataProvider::class, 'validCoinQuantities')]
    public function testItCreatesAValidCoinQuantity(int $quantity): void
    {
        $coinQuantity = CoinQuantity::create($quantity);

        self::assertSame($quantity, $coinQuantity->value());
        self::assertFalse($coinQuantity->isNegativeValue());
    }

    #[DataProviderExternal(CoinTestDataProvider::class, 'negativeCoinQuantities')]
    public function testItRejectsNegativeCoinQuantities(int $quantity): void
    {
        $this->expectException(CoinWithNegativeQuantity::class);

        CoinQuantity::create($quantity);
    }

    public function testItInitializesWithOne(): void
    {
        $coinQuantity = CoinQuantity::initialize();

        self::assertSame(1, $coinQuantity->value());
    }

    public function testItIncrementsTheQuantity(): void
    {
        $coinQuantity = CoinQuantity::create(5);
        $incrementedQuantity = $coinQuantity->increment();

        self::assertSame(6, $incrementedQuantity->value());
    }

    public function testItSubtractsTheQuantity(): void
    {
        $coinQuantity = CoinQuantity::create(5);
        $decrementedCoinQuantity = CoinQuantity::create(2);
        $decrementedQuantity = $coinQuantity->subtract($decrementedCoinQuantity);

        self::assertSame(3, $decrementedQuantity->value());
    }

    public function testItRejectsSubtractingMoreThanAvailable(): void
    {
        $coinQuantity = CoinQuantity::create(5);
        $decrementedCoinQuantity = CoinQuantity::create(10);
        $this->expectException(CoinWithNegativeQuantity::class);
        $coinQuantity->subtract($decrementedCoinQuantity);
    }
}
