<?php

namespace App\Tests\Coin\Domain;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinValue;
use PHPUnit\Framework\TestCase;

final class CoinTest extends TestCase
{
    public function testItIsCreatedWithIdValueAndQuantity(): void
    {
        $id = CoinId::generate();
        $value = CoinValue::create(0.10);
        $quantity = CoinQuantity::initialize();

        $coin = Coin::create($id, $value, $quantity);

        self::assertSame($id, $coin->id());
        self::assertSame($value, $coin->value());
        self::assertSame($quantity, $coin->quantity());
    }

    public function testItUpdatesQuantity(): void
    {
        $coin = Coin::create(
            CoinId::generate(),
            CoinValue::create(0.25),
            CoinQuantity::initialize()
        );

        $coin->increaseQuantity();

        self::assertSame(2, $coin->quantity()->value());
    }
}
