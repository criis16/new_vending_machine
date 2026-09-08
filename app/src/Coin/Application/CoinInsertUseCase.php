<?php

namespace App\Coin\Application;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRespository;
use App\Coin\Domain\CoinValue;

final readonly class CoinInsertUseCase
{
    public function __construct(private CoinsRespository $repository)
    {
    }

    public function __invoke(CoinsInsertDTO $dto): void
    {
        $coin = Coin::create(
            CoinId::generate(),
            CoinValue::create($dto->coin),
            CoinQuantity::initialize(),
        );

        $this->repository->save($coin);
    }
}
