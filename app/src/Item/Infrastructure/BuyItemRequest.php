<?php

namespace App\Item\Infrastructure;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BuyItemRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
    )
    {
    }
}
