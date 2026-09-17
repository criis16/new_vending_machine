<?php

namespace App\Tests\Item\Application;

use App\Item\Domain\Item;
use App\Item\Domain\ItemsRepository;
use App\Shared\Domain\Criteria\Criteria;

final class InMemoryItemsRepository implements ItemsRepository
{
    /** @var Item[] */
    private array $items = [];

    public function save(Item $item): void
    {
        $this->items[$item->id()->value()] = $item;
    }

    public function searchByCriteria(Criteria $criteria): array
    {
        return array_values($this->items);
    }

    public function findAll(): array
    {
        return array_values($this->items);
    }
}
