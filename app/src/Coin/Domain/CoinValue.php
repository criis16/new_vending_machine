<?php

namespace App\Coin\Domain;

use App\Shared\Domain\ValueObject\FloatValueObject;

final class CoinValue extends FloatValueObject
{
    public static function create(float $value): self
    {
        CoinType::fromValue($value);
        return new self($value);
    }
}
