<?php

namespace App\Tests\Item\Domain;

use App\Item\Domain\Exceptions\ItemNotValid;
use App\Item\Domain\ItemName;
use App\Tests\Item\Domain\DataProvider\ItemTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class ItemNameTest extends TestCase
{
    #[DataProviderExternal(ItemTestDataProvider::class, 'validItemNames')]
    public function testItCreatesAValidItemName(string $name, string $expected): void
    {
        $itemName = ItemName::create($name);

        self::assertSame($expected, $itemName->value());
    }

    #[DataProviderExternal(ItemTestDataProvider::class, 'invalidItemNames')]
    public function testItRejectsUnissuedItemNames(string $name): void
    {
        $this->expectException(ItemNotValid::class);

        ItemName::create($name);
    }
}
