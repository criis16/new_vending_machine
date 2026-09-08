<?php

namespace App\Coin\Domain\Exceptions;

final class CoinNotValid extends \InvalidArgumentException
{
    public function __construct(float $value)
    {
        parent::__construct(
            sprintf('Invalid coin value %s.', $value)
        );
    }
}
