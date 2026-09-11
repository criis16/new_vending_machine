<?php

namespace App\Tests\Shared\Domain\Criteria\DataProvider;

final class OrderDataProvider
{
    /** @return iterable<string, array{?string, ?string}> */
    public static function nullCombinations(): iterable
    {
        yield 'both null' => [null, null];
        yield 'order by only' => ['value', null];
        yield 'order only' => [null, 'asc'];
    }
}
