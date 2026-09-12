<?php

namespace App\Tests\Shared\Domain\Criteria\DataProvider;

use App\Shared\Domain\Criteria\OrderType;

final class OrderTypeDataProvider
{
    /** @return iterable<string, array{OrderType, bool}> */
    public static function orderTypes(): iterable
    {
        yield 'asc' => [OrderType::ASC, false];
        yield 'desc' => [OrderType::DESC, false];
        yield 'none' => [OrderType::NONE, true];
    }
}
