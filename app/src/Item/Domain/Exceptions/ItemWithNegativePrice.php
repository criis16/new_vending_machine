<?php

namespace App\Item\Domain\Exceptions;

final class ItemWithNegativePrice extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Item price can not be negative');
    }
}
