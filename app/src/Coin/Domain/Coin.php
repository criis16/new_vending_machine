<?php

namespace App\Coin\Domain;

final class Coin
{
    public function __construct(
        private readonly CoinId    $id,
        private readonly CoinValue $value,
        private CoinQuantity       $quantity
    )
    {
    }

    public static function create(
        CoinId       $id,
        CoinValue    $value,
        CoinQuantity $quantity
    ): self
    {
        return new self($id, $value, $quantity);
    }

    public function id(): CoinId
    {
        return $this->id;
    }

    public function value(): CoinValue
    {
        return $this->value;
    }

    public function quantity(): CoinQuantity
    {
        return $this->quantity;
    }

    public function increaseQuantity(): void
    {
        $this->quantity = $this->quantity->increment();
    }

    public function decreaseQuantity(CoinQuantity $coinQuantity): void
    {
        $this->quantity = $this->quantity->subtract($coinQuantity);
    }
}
