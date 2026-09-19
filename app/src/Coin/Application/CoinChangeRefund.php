<?php

namespace App\Coin\Application;

use App\Coin\Domain\CoinChange;
use App\Coin\Domain\CoinChangeCalculator;
use App\Coin\Domain\CoinChangeDispenser;
use App\Coin\Domain\CoinsRepository;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusRepository;

final readonly class CoinChangeRefund
{
    public function __construct(
        private CoinsRepository         $coinsRepository,
        private MachineStatusRepository $machineStatusRepository,
        private CoinChangeCalculator    $coinChangeCalculator,
        private CoinChangeDispenser     $coinChangeDispenser,
    )
    {
    }

    public function refund(MachineStatus $machineStatus, float $amount): CoinChange
    {
        $coins = $this->coinsRepository->findAll();
        $change = $this->coinChangeCalculator->calculate($amount, $coins);

        foreach ($this->coinChangeDispenser->dispense($change, $coins) as $coin) {
            $this->coinsRepository->save($coin);
        }

        $machineStatus->resetBalance();
        $this->machineStatusRepository->save($machineStatus);

        return $change;
    }
}
