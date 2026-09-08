<?php

namespace App\Coin\Domain\Exceptions;

final class CoinWithNegativeValue extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Coin value can not be negative');
    }
}
