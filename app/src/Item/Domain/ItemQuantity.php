<?php

namespace App\Item\Domain;

use App\Item\Domain\Exceptions\ItemWithNegativeQuantity;
use App\Shared\Domain\ValueObject\IntQuantityValueObject;

final class ItemQuantity extends IntQuantityValueObject
{
    public static function initialize(): self
    {
        return new self(1);
    }

    public static function create(int $quantity): self
    {
        $itemQuantity = new self($quantity);

        if ($itemQuantity->isNegativeValue()) {
            throw new ItemWithNegativeQuantity();
        }

        return $itemQuantity;
    }

    public function increment(): self
    {
        return new self($this->value() + 1);
    }

    public function subtract(self $itemQuantity): self
    {
        $newQuantity = $this->value() - $itemQuantity->value();

        if ($newQuantity < 0) {
            throw new ItemWithNegativeQuantity();
        }

        return new self($newQuantity);
    }

    public function add(self $itemQuantity): self
    {
        return new self($this->value() + $itemQuantity->value());
    }
}
