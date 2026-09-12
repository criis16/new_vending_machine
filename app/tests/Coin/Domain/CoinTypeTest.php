<?php

namespace App\Tests\Coin\Domain;

use App\Coin\Domain\CoinType;
use App\Coin\Domain\Exceptions\CoinNotValid;
use App\Tests\Coin\Domain\DataProvider\CoinTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class CoinTypeTest extends TestCase
{
    #[DataProviderExternal(CoinTestDataProvider::class, 'validCoinTypes')]
    public function testItResolvesValidCoinValues(float $value, CoinType $expected): void
    {
        self::assertSame($expected, CoinType::fromValue($value));
    }

    #[DataProviderExternal(CoinTestDataProvider::class, 'invalidCoinValues')]
    public function testItRejectsUnissuedCoinValues(float $value): void
    {
        $this->expectException(CoinNotValid::class);

        CoinType::fromValue($value);
    }

    public function testItResolvesOneEuroPassedAsFloatInteger(): void
    {
        self::assertSame(CoinType::OneEuro, CoinType::fromValue(1.0));
    }

    public function testItAllowsAlternativeFloatSpellingsOfSameValue(): void
    {
        self::assertSame(
            CoinType::TenCents,
            CoinType::fromValue((float)'0.100')
        );
    }
}
