<?php

namespace App\Item\Domain\Exceptions;

final class ItemNotAvailable extends \InvalidArgumentException
{
    public function __construct(string $value)
    {
        parent::__construct(
            sprintf('Item %s is not available.', $value)
        );
    }
}
