<?php

namespace App\Coin\Domain;

use App\Shared\Domain\ValueObject\IntQuantityValueObject;

final class CoinQuantity extends IntQuantityValueObject
{
    public static function initialize(): self
    {
        return new self(1);
    }

    public function increment(): self
    {
        return new self($this->value() + 1);
    }
}
