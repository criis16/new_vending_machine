<?php

namespace App\Item\Domain;

use App\Item\Domain\Exceptions\ItemWithNegativePrice;
use App\Shared\Domain\ValueObject\FloatValueObject;

final class ItemPrice extends FloatValueObject
{
    public static function create(float $price): self
    {
        $itemPrice = new self($price);

        if ($itemPrice->isNegativeValue()) {
            throw new ItemWithNegativePrice();
        }
        ItemType::fromValue($price);

        return $itemPrice;
    }

    public static function createFromName(ItemName $name): self
    {
        return self::create((float)ItemType::fromCaseName($name->value())->value);
    }
}
