<?php

namespace App\Coin\Domain;

use App\Coin\Domain\Exceptions\CoinNotValid;

enum CoinType: string
{
    case FiveCents = '0.05';
    case TenCents = '0.10';
    case TwentyFiveCents = '0.25';
    case OneEuro = '1.00';

    public static function fromValue(float $value): self
    {
        return self::tryFrom($value) ?? throw new CoinNotValid($value);
    }
}
