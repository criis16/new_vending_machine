<?php

namespace App\Shared\Domain\ValueObject;

abstract class IntQuantityValueObject
{
    private const int ZERO_QUANTITY = 0;

    public function __construct(protected int $value)
    {
    }

    final public function value(): int
    {
        return $this->value;
    }

    final public function isNegativeValue(): bool
    {
        return $this->value() < self::ZERO_QUANTITY;
    }
}
