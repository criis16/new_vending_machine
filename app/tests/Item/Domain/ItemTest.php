<?php

namespace App\Tests\Item\Domain;

use App\Item\Domain\Item;
use App\Item\Domain\ItemId;
use App\Item\Domain\ItemName;
use App\Item\Domain\ItemPrice;
use App\Item\Domain\ItemQuantity;
use PHPUnit\Framework\TestCase;

final class ItemTest extends TestCase
{
    public function testItIsCreatedWithIdNameQuantityAndPrice(): void
    {
        $id = ItemId::generate();
        $name = ItemName::create('water');
        $quantity = ItemQuantity::initialize();
        $price = ItemPrice::createFromName($name);

        $item = Item::create($id, $name, $quantity, $price);

        self::assertSame($id, $item->id());
        self::assertSame($name, $item->name());
        self::assertSame($quantity, $item->quantity());
        self::assertSame($price, $item->price());
    }

    public function testItIncreasesQuantity(): void
    {
        $item = Item::create(
            ItemId::generate(),
            ItemName::create('Water'),
            ItemQuantity::initialize(),
            ItemPrice::create(0.65)
        );

        $item->increaseQuantity();

        self::assertSame(2, $item->quantity()->value());
    }

    public function testItDecreasesQuantity(): void
    {
        $item = Item::create(
            ItemId::generate(),
            ItemName::create('Water'),
            ItemQuantity::initialize(),
            ItemPrice::create(0.65)
        );

        $item->increaseQuantity();
        $item->decreaseQuantity(ItemQuantity::create(1));

        self::assertSame(1, $item->quantity()->value());
    }

    public function testItAddsQuantity(): void
    {
        $item = Item::create(
            ItemId::generate(),
            ItemName::create('Water'),
            ItemQuantity::create(5),
            ItemPrice::create(0.65)
        );

        $item->addQuantity(ItemQuantity::create(3));

        self::assertSame(8, $item->quantity()->value());
    }
}
