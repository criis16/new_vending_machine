<?php

namespace App\MachineStatus\Domain;

use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceCannotBeNegative;
use App\Shared\Domain\ValueObject\FloatValueObject;

final class MachineStatusBalance extends FloatValueObject
{
    public static function initialize(): self
    {
        return new self(self::ZERO_QUANTITY);
    }

    public static function create(float $value): self
    {
        $balance = new self($value);

        if ($balance->isNegativeValue()) {
            throw new MachineStatusBalanceCannotBeNegative();
        }

        return $balance;
    }

    public function add(self $amount): self
    {
        return new self(round($this->value + $amount->value(), 2));
    }

    public function isEmpty(): bool
    {
        return $this->value === self::ZERO_QUANTITY;
    }
}
