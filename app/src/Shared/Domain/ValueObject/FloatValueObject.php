<?php

namespace App\Shared\Domain\ValueObject;

abstract class FloatValueObject
{
    protected const float ZERO_QUANTITY = 0.0;

    public function __construct(protected float $value)
    {
    }

    final public function value(): float
    {
        return $this->value;
    }

    final public function isNegativeValue(): bool
    {
        return $this->value() < self::ZERO_QUANTITY;
    }
}
