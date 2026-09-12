<?php

namespace App\Tests\Shared\Domain\Criteria;

use App\Shared\Domain\Criteria\FilterOperator;
use App\Tests\Shared\Domain\Criteria\DataProvider\FilterOperatorDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class FilterOperatorTypeTest extends TestCase
{
    #[DataProviderExternal(FilterOperatorDataProvider::class, 'operators')]
    public function testItKnowsIfItIsContaining(FilterOperator $operator, bool $expected): void
    {
        self::assertSame($expected, $operator->isContaining());
    }

    public function testItHasExplicitBackingValues(): void
    {
        self::assertSame('=', FilterOperator::EQUAL->value);
        self::assertSame('!=', FilterOperator::NOT_EQUAL->value);
        self::assertSame('>', FilterOperator::GT->value);
        self::assertSame('<', FilterOperator::LT->value);
        self::assertSame('CONTAINS', FilterOperator::CONTAINS->value);
        self::assertSame('NOT_CONTAINS', FilterOperator::NOT_CONTAINS->value);
    }

    public function testItResolvesFromBackingValues(): void
    {
        self::assertSame(FilterOperator::EQUAL, FilterOperator::from('='));
        self::assertSame(FilterOperator::NOT_EQUAL, FilterOperator::from('!='));
        self::assertSame(FilterOperator::GT, FilterOperator::from('>'));
        self::assertSame(FilterOperator::LT, FilterOperator::from('<'));
        self::assertSame(FilterOperator::CONTAINS, FilterOperator::from('CONTAINS'));
        self::assertSame(FilterOperator::NOT_CONTAINS, FilterOperator::from('NOT_CONTAINS'));
    }
}
