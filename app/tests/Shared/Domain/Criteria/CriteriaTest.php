<?php

namespace App\Tests\Shared\Domain\Criteria;

use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use App\Shared\Domain\Criteria\OrderBy;
use App\Shared\Domain\Criteria\OrderType;
use PHPUnit\Framework\TestCase;

final class CriteriaTest extends TestCase
{
    private static function buildFilters(): Filters
    {
        return Filters::fromValues([
            ['field' => 'value', 'operator' => '=', 'value' => '0.10'],
        ]);
    }

    public function testItHasFiltersWhenConstructedWithFilters(): void
    {
        $criteria = new Criteria(self::buildFilters(), Order::none());

        self::assertTrue($criteria->hasFilters());
        self::assertCount(1, $criteria->plainFilters());
    }

    public function testItHasNoFiltersWhenConstructedWithNoneFilters(): void
    {
        $criteria = new Criteria(Filters::none(), Order::none());

        self::assertFalse($criteria->hasFilters());
        self::assertSame([], $criteria->plainFilters());
    }

    public function testItHasOrderWhenConstructedWithOrder(): void
    {
        $criteria = new Criteria(
            Filters::none(),
            Order::createDesc(new OrderBy('a column value'))
        );

        self::assertTrue($criteria->hasOrder());
        self::assertSame(OrderType::DESC, $criteria->order()->orderType());
    }

    public function testItHasNoOrderWhenConstructedWithNoneOrder(): void
    {
        $criteria = new Criteria(Filters::none(), Order::none());

        self::assertFalse($criteria->hasOrder());
        self::assertSame(OrderType::NONE, $criteria->order()->orderType());
    }

    public function testItDefaultsToNoOffsetOrLimit(): void
    {
        $criteria = new Criteria(Filters::none(), Order::none());

        self::assertNull($criteria->offset());
        self::assertNull($criteria->limit());
    }

    public function testItAcceptsOffsetAndLimit(): void
    {
        $criteria = new Criteria(
            Filters::none(),
            Order::none(),
            10,
            5
        );

        self::assertSame(10, $criteria->offset());
        self::assertSame(5, $criteria->limit());
    }

    public function testItExposesFiltersInstanceAndPlainArray(): void
    {
        $filters = self::buildFilters();
        $criteria = new Criteria($filters, Order::none());

        self::assertSame($filters, $criteria->filters());
        self::assertSame($filters->filters(), $criteria->plainFilters());
    }

}
