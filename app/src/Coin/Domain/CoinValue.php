<?php

namespace App\Coin\Domain;

use App\Coin\Domain\Exceptions\CoinWithNegativeValue;
use App\Shared\Domain\ValueObject\FloatValueObject;

final class CoinValue extends FloatValueObject
{
    public static function create(float $value): self
    {
        $coinValue = new self($value);

        if ($coinValue->isNegativeValue()) {
            throw new CoinWithNegativeValue();
        }
        CoinType::fromValue($value);

        return $coinValue;
    }
}
