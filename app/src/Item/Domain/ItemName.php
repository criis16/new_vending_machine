<?php

namespace App\Item\Domain;

use App\Item\Domain\Exceptions\ItemNotValid;
use App\Shared\Domain\ValueObject\StringValueObject;

final class ItemName extends StringValueObject
{
    public static function create(string $name): self
    {
        if (array_any(ItemType::cases(), fn($case) => strcasecmp($case->name, $name) === 0)) {
            return new self(ucfirst(strtolower($name)));
        }

        throw new ItemNotValid($name);
    }
}
