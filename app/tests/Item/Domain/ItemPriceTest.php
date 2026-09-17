<?php

namespace App\Tests\Item\Domain;

use App\Item\Domain\Exceptions\ItemNotValid;
use App\Item\Domain\Exceptions\ItemWithNegativePrice;
use App\Item\Domain\ItemName;
use App\Item\Domain\ItemPrice;
use App\Tests\Item\Domain\DataProvider\ItemTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class ItemPriceTest extends TestCase
{
    #[DataProviderExternal(ItemTestDataProvider::class, 'validItemPrices')]
    public function testItCreatesAValidItemPrice(float $price): void
    {
        $itemPrice = ItemPrice::create($price);

        self::assertSame($price, $itemPrice->value());
        self::assertFalse($itemPrice->isNegativeValue());
    }

    #[DataProviderExternal(ItemTestDataProvider::class, 'invalidItemPrices')]
    public function testItRejectsUnissuedItemPrices(float $price): void
    {
        $this->expectException(ItemNotValid::class);

        ItemPrice::create($price);
    }

    #[DataProviderExternal(ItemTestDataProvider::class, 'negativeItemPrices')]
    public function testItRejectsNegativeItemPrices(float $price): void
    {
        $this->expectException(ItemWithNegativePrice::class);

        ItemPrice::create($price);
    }

    public function testItAcceptsOneEuroPassedAsFloatOne(): void
    {
        self::assertSame(1.0, ItemPrice::create(1.0)->value());
    }

    public function testItCreatesAPriceFromAName(): void
    {
        self::assertSame(0.65, ItemPrice::createFromName(ItemName::create('water'))->value());
        self::assertSame(1.50, ItemPrice::createFromName(ItemName::create('SODA'))->value());
    }
}
