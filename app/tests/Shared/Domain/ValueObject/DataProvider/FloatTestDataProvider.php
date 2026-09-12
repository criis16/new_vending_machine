<?php

namespace App\Tests\Shared\Domain\ValueObject\DataProvider;

final class FloatTestDataProvider
{
    /** @return iterable<string, array{float}> */
    public static function anyFloats(): iterable
    {
        yield 'zero' => [0.0];
        yield 'positive' => [0.5];
        yield 'large positive' => [999.99];
        yield 'negative' => [-0.5];
        yield 'large negative' => [-999.99];
        yield 'epsilon away from zero' => [PHP_FLOAT_EPSILON];
    }

    /** @return iterable<string, array{float}> */
    public static function nonNegativeValues(): iterable
    {
        yield 'zero' => [0.0];
        yield 'positive' => [0.5];
        yield 'epsilon' => [PHP_FLOAT_EPSILON];
    }

    /** @return iterable<string, array{float}> */
    public static function negativeValues(): iterable
    {
        yield 'minus' => [-0.5];
        yield 'large negative' => [-999.99];
        yield 'negative epsilon' => [-PHP_FLOAT_EPSILON];
    }
}
