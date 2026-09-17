<?php

namespace App\Item\Domain\Exceptions;

final class ItemNotValid extends \InvalidArgumentException
{
    public function __construct(string $value)
    {
        parent::__construct(
            sprintf('Invalid item %s.', $value)
        );
    }
}
