<?php

namespace App\Tests\Item\Domain;

use App\Item\Domain\ItemId;
use PHPUnit\Framework\TestCase;

final class ItemIdTest extends TestCase
{
    public function testItGeneratesAValidV4UuidCoinId(): void
    {
        $id = ItemId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id->value()
        );
    }
}
