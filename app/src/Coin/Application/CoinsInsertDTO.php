<?php

namespace App\Coin\Application;
final readonly class CoinsInsertDTO
{
    public function __construct(
        public float $coin,
    )
    {
    }
}
