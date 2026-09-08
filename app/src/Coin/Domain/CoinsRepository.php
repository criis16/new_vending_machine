<?php

namespace App\Coin\Domain;

use App\Shared\Domain\Criteria\Criteria;

interface CoinsRepository
{
    public function save(Coin $coin): void;

    public function searchByCriteria(Criteria $criteria): array;
}
