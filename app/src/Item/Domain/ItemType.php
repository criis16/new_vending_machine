<?php

namespace App\Item\Domain;

use App\Item\Domain\Exceptions\ItemNotValid;

enum ItemType: string
{
    case Water = '0.65';
    case Juice = '1.00';
    case Soda = '1.50';

    public static function fromValue(float $value): self
    {
        foreach (self::cases() as $case) {
            if ((float)$case->value === $value) {
                return $case;
            }
        }
        throw new ItemNotValid($value);
    }

    public static function fromCaseName(string $name): self
    {
        foreach (self::cases() as $case) {
            if ( strcasecmp($case->name, $name) === 0) {
                return $case;
            }
        }

        throw new ItemNotValid($name);
    }
}
