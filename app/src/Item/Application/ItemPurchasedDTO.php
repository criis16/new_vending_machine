<?php

namespace App\Item\Application;

final readonly class ItemPurchasedDTO
{
    /**
     * @param array<string, int> $coins coin type value => units returned as change
     */
    public function __construct(
        public string $item,
        public array  $coins,
    )
    {
    }
}
