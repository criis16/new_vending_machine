<?php

namespace App\Tests\Coin\Application;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinsRepository;
use App\Shared\Domain\Criteria\Criteria;

final class InMemoryCoinsRepository implements CoinsRepository
{
    /** @var Coin[] */
    private array $coins = [];

    public function save(Coin $coin): void
    {
        $this->coins[$coin->id()->value()] = $coin;
    }

    public function searchByCriteria(Criteria $criteria): array
    {
        return array_values($this->coins);
    }

    public function findAll(): array
    {
        return array_values($this->coins);
    }
}
