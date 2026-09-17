<?php

namespace App\Item\Domain\Exceptions;

final class ItemWithNegativeQuantity extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Item quantity can not be negative');
    }
}
