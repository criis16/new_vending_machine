<?php

namespace App\Tests\Coin\Domain\DataProvider;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinValue;

final class CoinChangeCalculatorTestDataProvider
{
    /**
     * @return iterable<string, array{float, Coin[], array<string, int>}>
     */
    public static function exactChange(): iterable
    {
        yield 'single one euro' => [1.00, [self::coin(1.00, 1)], ['1.00' => 1]];

        yield 'quarter and nickel' => [
            0.30,
            [self::coin(0.25, 1), self::coin(0.05, 1)],
            ['0.25' => 1, '0.05' => 1],
        ];

        yield 'greedy counterexample resolved with dimes' => [
            0.30,
            [self::coin(0.25, 1), self::coin(0.10, 3)],
            ['0.10' => 3],
        ];

        yield 'exceeds single coin stock' => [
            0.50,
            [self::coin(0.25, 1), self::coin(0.10, 2), self::coin(0.05, 1)],
            ['0.25' => 1, '0.10' => 2, '0.05' => 1],
        ];

        yield 'aggregates quantity across same type coins' => [
            0.50,
            [self::coin(0.10, 2), self::coin(0.10, 3), self::coin(1.00, 0)],
            ['0.10' => 5],
        ];
    }

    private static function coin(float $value, int $quantity): Coin
    {
        return Coin::create(
            CoinId::generate(),
            CoinValue::create($value),
            CoinQuantity::create($quantity)
        );
    }
}
