<?php

namespace App\Tests\Coin\Domain;

use App\Coin\Domain\CoinValue;
use App\Coin\Domain\Exceptions\CoinNotValid;
use App\Coin\Domain\Exceptions\CoinWithNegativeValue;
use App\Tests\Coin\Domain\DataProvider\CoinTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class CoinValueTest extends TestCase
{
    #[DataProviderExternal(CoinTestDataProvider::class, 'validCoinValues')]
    public function testItCreatesAValidCoinValue(float $value): void
    {
        $coinValue = CoinValue::create($value);

        self::assertSame($value, $coinValue->value());
        self::assertFalse($coinValue->isNegativeValue());
    }

    #[DataProviderExternal(CoinTestDataProvider::class, 'invalidCoinValues')]
    public function testItRejectsUnissuedCoinValues(float $value): void
    {
        $this->expectException(CoinNotValid::class);

        CoinValue::create($value);
    }

    #[DataProviderExternal(CoinTestDataProvider::class, 'negativeCoinValues')]
    public function testItRejectsNegativeCoinValues(float $value): void
    {
        $this->expectException(CoinWithNegativeValue::class);

        CoinValue::create($value);
    }

    public function testItAcceptsOneEuroPassedAsFloatOne(): void
    {
        self::assertSame(1.0, CoinValue::create(1.0)->value());
    }
}
