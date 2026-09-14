<?php

namespace App\Coin\Domain;

use App\Coin\Domain\Exceptions\CoinWithNegativeQuantity;
use App\Shared\Domain\ValueObject\IntQuantityValueObject;

final class CoinQuantity extends IntQuantityValueObject
{
    public static function initialize(): self
    {
        return new self(1);
    }

    public static function create(int $quantity): self
    {
        $coinQuantity = new self($quantity);

        if ($coinQuantity->isNegativeValue()) {
            throw new CoinWithNegativeQuantity();
        }

        return $coinQuantity;
    }

    public function increment(): self
    {
        return new self($this->value() + 1);
    }

    public function subtract(self $coinQuantity): self
    {
        $newQuantity = $this->value() - $coinQuantity->value();

        if ($newQuantity < 0) {
            throw new CoinWithNegativeQuantity();
        }

        return new self($newQuantity);
    }
}
