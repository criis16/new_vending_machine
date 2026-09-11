<?php

namespace App\Tests\Shared\Domain\Criteria;

use App\Shared\Domain\Criteria\Order;
use App\Shared\Domain\Criteria\OrderBy;
use App\Shared\Domain\Criteria\OrderType;
use App\Tests\Shared\Domain\Criteria\DataProvider\OrderDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testNoneOrderHasEmptyOrderByAndNoneType(): void
    {
        $order = Order::none();

        self::assertSame('', $order->orderBy()->value());
        self::assertSame(OrderType::NONE, $order->orderType());
        self::assertTrue($order->isNone());
    }

    public function testItCreatesDescendingOrder(): void
    {
        $order = Order::createDesc(new OrderBy('quantity'));

        self::assertSame('quantity', $order->orderBy()->value());
        self::assertSame(OrderType::DESC, $order->orderType());
        self::assertFalse($order->isNone());
    }

    #[DataProviderExternal(OrderDataProvider::class, 'nullCombinations')]
    public function testItIsNoneWhenAnyOfTheValuesIsNull(?string $orderBy, ?string $order): void
    {
        self::assertTrue(Order::fromValues($orderBy, $order)->isNone());
    }

    public function testItBuildsOrderFromValues(): void
    {
        $order = Order::fromValues('value', 'asc');

        self::assertSame('value', $order->orderBy()->value());
        self::assertSame(OrderType::ASC, $order->orderType());
        self::assertFalse($order->isNone());
    }

    public function testItSerializesFieldAndType(): void
    {
        self::assertSame('value.asc', Order::fromValues('value', 'asc')->serialize());
        self::assertSame('.none', Order::none()->serialize());
    }
}
