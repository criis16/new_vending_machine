<?php

namespace App\Coin\Infrastructure\Persistence\Doctrine;

use App\Coin\Domain\CoinId;
use App\Shared\Infrastructure\Persistence\Doctrine\UuidType;

final class CoinIdType extends UuidType
{
    protected function typeClassName(): string
    {
        return CoinId::class;
    }
}
