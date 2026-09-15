<?php

namespace App\Tests\Coin\Domain\DataProvider;

use App\Coin\Domain\CoinType;

final class CoinChangeTestDataProvider
{
    /**
     * @return iterable<string, array{array<string, int>, int}>
     */
    public static function totalInCents(): iterable
    {
        yield 'single coin' => [['1.00' => 1], 100];
        yield 'multiple types' => [['1.00' => 1, '0.25' => 2], 150];
        yield 'five cents float precision' => [['0.05' => 1, '0.10' => 1], 15];
        yield 'empty' => [[], 0];
    }

    /**
     * @return iterable<string, array{array<string, int>, CoinType, int}>
     */
    public static function unitsFor(): iterable
    {
        yield 'requested type present' => [['0.10' => 3], CoinType::TenCents, 3];
        yield 'requested type absent' => [['0.10' => 3], CoinType::OneEuro, 0];
    }
}
