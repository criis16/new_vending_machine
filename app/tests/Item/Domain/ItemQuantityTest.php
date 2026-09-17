<?php

namespace App\Tests\Item\Domain;

use App\Item\Domain\Exceptions\ItemWithNegativeQuantity;
use App\Item\Domain\ItemQuantity;
use App\Tests\Item\Domain\DataProvider\ItemTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class ItemQuantityTest extends TestCase
{
    #[DataProviderExternal(ItemTestDataProvider::class, 'validItemQuantities')]
    public function testItCreatesAValidItemQuantity(int $quantity): void
    {
        $itemQuantity = ItemQuantity::create($quantity);

        self::assertSame($quantity, $itemQuantity->value());
        self::assertFalse($itemQuantity->isNegativeValue());
    }

    #[DataProviderExternal(ItemTestDataProvider::class, 'negativeItemQuantities')]
    public function testItRejectsNegativeItemQuantities(int $quantity): void
    {
        $this->expectException(ItemWithNegativeQuantity::class);

        ItemQuantity::create($quantity);
    }

    public function testItInitializesWithOne(): void
    {
        $itemQuantity = ItemQuantity::initialize();

        self::assertSame(1, $itemQuantity->value());
    }

    public function testItIncrementsTheQuantity(): void
    {
        $itemQuantity = ItemQuantity::create(5);
        $incrementedQuantity = $itemQuantity->increment();

        self::assertSame(6, $incrementedQuantity->value());
    }

    public function testItSubtractsTheQuantity(): void
    {
        $itemQuantity = ItemQuantity::create(5);
        $decrementedItemQuantity = ItemQuantity::create(2);
        $decrementedQuantity = $itemQuantity->subtract($decrementedItemQuantity);

        self::assertSame(3, $decrementedQuantity->value());
    }

    public function testItRejectsSubtractingMoreThanAvailable(): void
    {
        $itemQuantity = ItemQuantity::create(5);
        $decrementedItemQuantity = ItemQuantity::create(10);

        $this->expectException(ItemWithNegativeQuantity::class);
        $itemQuantity->subtract($decrementedItemQuantity);
    }

    public function testItIncreasesTheQuantity(): void
    {
        $itemQuantity = ItemQuantity::create(5);
        $incrementedItemQuantity = ItemQuantity::create(3);
        $increasedQuantity = $itemQuantity->add($incrementedItemQuantity);

        self::assertSame(8, $increasedQuantity->value());
    }
}
