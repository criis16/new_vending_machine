<?php

namespace App\Tests\Shared\Domain\Criteria\DataProvider;

use App\Shared\Domain\Criteria\FilterOperator;

final class FilterOperatorDataProvider
{
    /** @return iterable<string, array{FilterOperator, bool}> */
    public static function operators(): iterable
    {
        yield 'equal' => [FilterOperator::EQUAL, false];
        yield 'not equal' => [FilterOperator::NOT_EQUAL, false];
        yield 'greater than' => [FilterOperator::GT, false];
        yield 'less than' => [FilterOperator::LT, false];
        yield 'contains' => [FilterOperator::CONTAINS, true];
        yield 'not contains' => [FilterOperator::NOT_CONTAINS, true];
    }
}
