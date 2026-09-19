<?php

namespace App\Coin\Application;

use App\Coin\Domain\CoinChange;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsEmpty;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusRepository;
use App\Shared\Application\TransactionalService;

final readonly class CoinsGetBackUseCase
{
    public function __construct(
        private MachineStatusRepository $machineStatusRepository,
        private TransactionalService    $transactionalService,
        private CoinChangeRefund        $coinChangeRefund
    )
    {
    }

    public function __invoke(): CoinsGetBackDTO
    {
        $machineStatus = $this->findMachineStatusWithBalance();
        $change = $this->transactionalService->execute(
            fn(): CoinChange => $this->coinChangeRefund->refund($machineStatus, $machineStatus->balance()->value())
        );

        return new CoinsGetBackDTO(iterator_to_array($change));
    }

    private function findMachineStatusWithBalance(): MachineStatus
    {
        $machineStatus = $this->machineStatusRepository->find();
        if (empty($machineStatus) || $machineStatus->balance()->isEmpty()) {
            throw new MachineStatusBalanceIsEmpty();
        }

        return $machineStatus;
    }
}
