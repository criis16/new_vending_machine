<?php

namespace App\Item\Infrastructure\Persistence\Doctrine;

use App\Item\Domain\ItemId;
use App\Shared\Infrastructure\Persistence\Doctrine\UuidType;

final class ItemIdType extends UuidType
{
    protected function typeClassName(): string
    {
        return ItemId::class;
    }
}
