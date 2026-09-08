<?php

namespace App\Coin\Infrastructure;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CoinsInsertRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public float $coin,
    )
    {
    }
}
