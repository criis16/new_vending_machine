<?php

namespace App\Coin\Domain\Exceptions;

final class CoinWithNegativeQuantity extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Coin quantity can not be negative');
    }
}
