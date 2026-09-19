<?php

namespace App\Item\Application;

use App\Coin\Application\CoinChangeRefund;
use App\Coin\Domain\CoinChange;
use App\Item\Domain\Exceptions\ItemNotAvailable;
use App\Item\Domain\Item;
use App\Item\Domain\ItemName;
use App\Item\Domain\ItemQuantity;
use App\Item\Domain\ItemsRepository;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsEmpty;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsNotEnough;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusRepository;
use App\Shared\Application\TransactionalService;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;

final readonly class BuyItemUseCase
{
    public function __construct(
        private ItemsRepository         $itemsRepository,
        private MachineStatusRepository $machineStatusRepository,
        private CoinChangeRefund $coinChangeRefund,
        private TransactionalService    $transactionalService
    )
    {
    }

    public function __invoke(BuyItemDTO $dto): ItemPurchasedDTO
    {
        $itemName = ItemName::create($dto->item);
        $item = $this->findAvailableItem($itemName);

        $machineStatus = $this->findMachineStatusWithBalance();
        if ($this->balanceLessThanPrice($machineStatus, $item)) {
            throw new MachineStatusBalanceIsNotEnough();
        }

        $changeAmount = round($machineStatus->balance()->value() - $item->price()->value(), 2);

        $change = $this->transactionalService->execute(function () use ($item, $machineStatus, $changeAmount): CoinChange {
            $item->decreaseQuantity(ItemQuantity::create(1));
            $this->itemsRepository->save($item);

            return $this->coinChangeRefund->refund($machineStatus, $changeAmount);
        });

        return new ItemPurchasedDTO($itemName->value(), iterator_to_array($change));
    }

    private function findAvailableItem(ItemName $itemName): Item
    {
        $criteria = new Criteria(
            Filters::fromValues([
                ['field' => 'value', 'operator' => '=', 'value' => $itemName->value()],
            ]),
            Order::none()
        );

        $existing = $this->itemsRepository->searchByCriteria($criteria);

        if (empty($existing) || $existing[0]->quantity()->value() === 0) {
            throw new ItemNotAvailable($itemName->value());
        }

        return reset($existing);
    }

    private function findMachineStatusWithBalance(): MachineStatus
    {
        $machineStatus = $this->machineStatusRepository->find();
        if (empty($machineStatus) || $machineStatus->balance()->isEmpty()) {
            throw new MachineStatusBalanceIsEmpty();
        }

        return $machineStatus;
    }

    private function balanceLessThanPrice(MachineStatus $machineStatus, Item $item): bool
    {
        $balanceCents = (int)round($machineStatus->balance()->value() * 100);
        $priceCents = (int)round($item->price()->value() * 100);

        return $balanceCents < $priceCents;
    }
}
