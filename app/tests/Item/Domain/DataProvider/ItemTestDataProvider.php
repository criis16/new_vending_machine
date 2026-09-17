<?php

namespace App\Tests\Item\Domain\DataProvider;

final class ItemTestDataProvider
{
    /**
     * @return iterable<string, array{string}>
     *
     */
    public static function validItemNames(): iterable
    {
        yield 'water lowercase' => ['water', 'Water'];
        yield 'water uppercase' => ['WATER', 'Water'];
        yield 'juice exact case' => ['Juice', 'Juice'];
        yield 'soda lowercase' => ['soda', 'Soda'];

    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidItemNames(): iterable
    {
        yield 'empty name' => [''];
        yield 'unissued item' => ['Chips'];
        yield 'unissued item with whitespace' => [' Water'];
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function validItemQuantities(): iterable
    {
        yield 'one item' => [1];
        yield 'ten items' => [10];
        yield 'twenty five items' => [25];
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function negativeItemQuantities(): iterable
    {
        yield 'negative one item' => [-1];
        yield 'negative ten items' => [-10];
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function validItemPrices(): iterable
    {
        yield 'water price' => [0.65];
        yield 'juice price' => [1.00];
        yield 'soda price' => [1.50];
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function invalidItemPrices(): iterable
    {
        yield 'zero' => [0.0];
        yield 'twenty cents' => [0.20];
        yield 'ninety nine cents' => [0.99];
        yield 'two euros' => [2.00];
        yield 'epsilon away from water price' => [0.65 + PHP_FLOAT_EPSILON];
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function negativeItemPrices(): iterable
    {
        yield 'negative water price' => [-0.65];
        yield 'negative soda price' => [-1.50];
    }
}
