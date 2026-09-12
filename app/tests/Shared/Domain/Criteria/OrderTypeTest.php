<?php

namespace App\Tests\Shared\Domain\Criteria;

use App\Shared\Domain\Criteria\OrderType;
use App\Tests\Shared\Domain\Criteria\DataProvider\OrderTypeDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class OrderTypeTest extends TestCase
{
    #[DataProviderExternal(OrderTypeDataProvider::class, 'orderTypes')]
    public function testItKnowsIfItIsNone(OrderType $type, bool $expected): void
    {
        self::assertSame($expected, $type->isNone());
    }

    public function testItHasExplicitBackingValues(): void
    {
        self::assertSame('asc', OrderType::ASC->value);
        self::assertSame('desc', OrderType::DESC->value);
        self::assertSame('none', OrderType::NONE->value);
    }

    public function testItResolvesFromBackingValues(): void
    {
        self::assertSame(OrderType::ASC, OrderType::from('asc'));
        self::assertSame(OrderType::DESC, OrderType::from('desc'));
        self::assertSame(OrderType::NONE, OrderType::from('none'));
    }
}
