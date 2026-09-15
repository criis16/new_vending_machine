<?php

namespace App\Coin\Application;
final readonly class CoinsGetBackDTO
{
    /**
     * @param array<string, int> $coins coin type value => units returned
     */
    public function __construct(public array $coins)
    {
    }

}
