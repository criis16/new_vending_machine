<?php

namespace App\Item\Domain;

final class Item
{
    public function __construct(
        private readonly ItemId    $id,
        private readonly ItemName  $name,
        private ItemQuantity       $quantity,
        private readonly ItemPrice $price
    )
    {
    }

    public static function create(
        ItemId       $id,
        ItemName     $name,
        ItemQuantity $quantity,
        ItemPrice    $price
    ): self
    {
        return new self($id, $name, $quantity, $price);
    }

    public function id(): ItemId
    {
        return $this->id;
    }

    public function name(): ItemName
    {
        return $this->name;
    }

    public function quantity(): ItemQuantity
    {
        return $this->quantity;
    }

    public function price(): ItemPrice
    {
        return $this->price;
    }

    public function increaseQuantity(): void
    {
        $this->quantity = $this->quantity->increment();
    }

    public function decreaseQuantity(ItemQuantity $itemQuantity): void
    {
        $this->quantity = $this->quantity->subtract($itemQuantity);
    }

    public function addQuantity(ItemQuantity $itemQuantity): void
    {
        $this->quantity = $this->quantity->add($itemQuantity);
    }
}
