<?php

namespace App\Item\Application;

final readonly class BuyItemDTO
{
    public function __construct(
        public string $item,
    )
    {
    }
}
