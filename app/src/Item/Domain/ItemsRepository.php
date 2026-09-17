<?php

namespace App\Item\Domain;

use App\Shared\Domain\Criteria\Criteria;

interface ItemsRepository
{
    public function save(Item $item): void;

    public function searchByCriteria(Criteria $criteria): array;

    public function findAll(): array;
}
