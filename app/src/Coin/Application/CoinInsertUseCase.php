<?php

namespace App\Coin\Application;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinValue;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;

final readonly class CoinInsertUseCase
{
    public function __construct(private CoinsRepository $repository)
    {
    }

    public function __invoke(CoinsInsertDTO $dto): void
    {
        $coinValue = CoinValue::create($dto->coin);

        $criteria = new Criteria(
            Filters::fromValues([
                ['field' => 'value', 'operator' => '=', 'value' => $coinValue->value()],
            ]),
            Order::none()
        );

        $existing = $this->repository->searchByCriteria($criteria);

        if (empty($existing)) {
            $coin = Coin::create(
                CoinId::generate(),
                $coinValue,
                CoinQuantity::initialize()
            );
        } else {
            $coin = reset($existing);
            $coin->updateQuantity($coin->quantity()->increment());
        }

        $this->repository->save($coin);
    }
}
