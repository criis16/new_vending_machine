<?php

namespace App\Tests\Shared\Domain\ValueObject\DataProvider;

final class IntQuantityTestDataProvider
{
    /** @return iterable<string, array{int}> */
    public static function anyIntegers(): iterable
    {
        yield 'zero' => [0];
        yield 'positive' => [1];
        yield 'large positive' => [999999];
        yield 'negative' => [-1];
        yield 'large negative' => [-999999];
    }

    /** @return iterable<string, array{int}> */
    public static function nonNegativeValues(): iterable
    {
        yield 'zero' => [0];
        yield 'positive' => [1];
        yield 'large' => [999999];
    }

    /** @return iterable<string, array{int}> */
    public static function negativeValues(): iterable
    {
        yield 'minus one' => [-1];
        yield 'large negative' => [-999999];
    }
}
