<?php

namespace App\MachineStatus\Application;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinValue;
use App\Item\Domain\Item;
use App\Item\Domain\ItemId;
use App\Item\Domain\ItemName;
use App\Item\Domain\ItemPrice;
use App\Item\Domain\ItemQuantity;
use App\Item\Domain\ItemsRepository;
use App\Shared\Application\TransactionalService;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;

final readonly class MachineServiceUseCase
{
    public function __construct(
        private CoinsRepository      $coinsRepository,
        private ItemsRepository      $itemsRepository,
        private TransactionalService $transactionalService
    )
    {
    }

    /**
     * @param array<string, int> $coins coin value => quantity
     * @param array<string, int> $items item name => quantity
     */
    public function __invoke(array $coins, array $items): void
    {
        $this->transactionalService->execute(function () use ($coins, $items): void {
            foreach ($coins as $value => $quantity) {
                $this->stockCoin(CoinValue::create((float)$value), CoinQuantity::create($quantity));
            }

            foreach ($items as $name => $quantity) {
                $this->stockItem(ItemName::create($name), ItemQuantity::create($quantity));
            }
        });
    }

    private function stockCoin(CoinValue $value, CoinQuantity $quantity): void
    {
        $existing = $this->coinsRepository->searchByCriteria(
            new Criteria(
                Filters::fromValues([['field' => 'value', 'operator' => '=', 'value' => $value->value()]]),
                Order::none()
            )
        );

        if (empty($existing)) {
            $coin = Coin::create(CoinId::generate(), $value, $quantity);
        } else {
            $coin = reset($existing);
            $coin->addQuantity($quantity);
        }

        $this->coinsRepository->save($coin);
    }

    private function stockItem(ItemName $name, ItemQuantity $quantity): void
    {
        $existing = $this->itemsRepository->searchByCriteria(
            new Criteria(
                Filters::fromValues([['field' => 'value', 'operator' => '=', 'value' => $name->value()]]),
                Order::none()
            )
        );

        if (empty($existing)) {
            $item = Item::create(
                ItemId::generate(),
                $name,
                $quantity,
                ItemPrice::createFromName($name)
            );
        } else {
            $item = reset($existing);
            $item->addQuantity($quantity);
        }

        $this->itemsRepository->save($item);
    }
}
