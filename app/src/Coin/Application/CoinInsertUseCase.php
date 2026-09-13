<?php

namespace App\Coin\Application;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinValue;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\MachineStatus\Domain\MachineStatusId;
use App\MachineStatus\Domain\MachineStatusRepository;
use App\Shared\Application\TransactionalService;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;

final readonly class CoinInsertUseCase
{
    public function __construct(
        private CoinsRepository         $repository,
        private MachineStatusRepository $machineStatusRepository,
        private TransactionalService    $transactionalService
    )
    {
    }

    public function __invoke(CoinsInsertDTO $dto): void
    {
        $coinValue = CoinValue::create($dto->coin);
        $coin = $this->findCoin($coinValue);

        $machineStatus = $this->findMachineStatus();
        $this->transactionalService->execute(function () use ($coinValue, $coin, $machineStatus): void {
            $machineStatus->credit(MachineStatusBalance::create($coinValue->value()));

            if (empty($coin)) {
                $coin = Coin::create(CoinId::generate(), $coinValue, CoinQuantity::initialize());
            } else {
                $coin->increaseQuantity();
            }

            $this->repository->save($coin);
            $this->machineStatusRepository->save($machineStatus);
        });
    }

    private function findCoin(CoinValue $coinValue): ?Coin
    {
        $criteria = new Criteria(
            Filters::fromValues([
                ['field' => 'value', 'operator' => '=', 'value' => $coinValue->value()],
            ]),
            Order::none()
        );

        $existing = $this->repository->searchByCriteria($criteria);

        return empty($existing) ? null : reset($existing);
    }

    private function findMachineStatus(): ?MachineStatus
    {
        $machineStatus = $this->machineStatusRepository->find();

        if (empty($machineStatus)) {
            $machineStatus = MachineStatus::create(
                MachineStatusId::generate(),
                MachineStatusBalance::initialize()
            );
        }

        return $machineStatus;
    }
}
