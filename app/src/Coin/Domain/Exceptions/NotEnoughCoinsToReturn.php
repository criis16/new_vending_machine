<?php

namespace App\Coin\Domain\Exceptions;

final class NotEnoughCoinsToReturn extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('There are not enough coins to return the current balance.');
    }
}
