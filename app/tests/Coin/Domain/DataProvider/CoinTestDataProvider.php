<?php

namespace App\Tests\Coin\Domain\DataProvider;

use App\Coin\Domain\CoinType;

final class CoinTestDataProvider
{
    /**
     * @return iterable<string, array{float, CoinType}>
     */
    public static function validCoinTypes(): iterable
    {
        yield 'five cents' => [0.05, CoinType::FiveCents];
        yield 'ten cents' => [0.10, CoinType::TenCents];
        yield 'twenty five cents' => [0.25, CoinType::TwentyFiveCents];
        yield 'one euro' => [1.00, CoinType::OneEuro];
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function validCoinValues(): iterable
    {
        yield 'five cents' => [0.05];
        yield 'ten cents' => [0.10];
        yield 'twenty five cents' => [0.25];
        yield 'one euro' => [1.00];
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function validCoinQuantities(): iterable
    {
        yield 'one coin' => [1];
        yield 'ten coins' => [10];
        yield 'twenty five coins' => [25];

    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function invalidCoinValues(): iterable
    {
        yield 'zero' => [0.0];
        yield 'twenty cents' => [0.20];
        yield 'fifty cents' => [0.50];
        yield 'beyond max' => [1.05];
        yield 'epsilon away from five cents' => [0.05 + PHP_FLOAT_EPSILON];
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function negativeCoinValues(): iterable
    {
        yield 'negative five cents' => [-0.05];
        yield 'negative one euro' => [-1.00];
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function negativeCoinQuantities(): iterable
    {

        yield 'negative one coin' => [-1];
        yield 'negative ten coins' => [-10];
    }
}
