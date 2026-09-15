<?php

namespace App\Coin\Application;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinChange;
use App\Coin\Domain\CoinChangeCalculator;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinType;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsEmpty;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusRepository;
use App\Shared\Application\TransactionalService;

final readonly class CoinsGetBackUseCase
{
    public function __construct(
        private CoinsRepository         $coinsRepository,
        private MachineStatusRepository $machineStatusRepository,
        private CoinChangeCalculator    $coinChangeCalculator,
        private TransactionalService    $transactionalService
    )
    {
    }

    public function __invoke(): CoinsGetBackDTO
    {
        $machineStatus = $this->findMachineStatusWithBalance();
        $coins = $this->coinsRepository->findAll();

        $change = $this->coinChangeCalculator->calculate($machineStatus->balance()->value(), $coins);

        $this->transactionalService->execute(function () use ($machineStatus, $coins, $change): void {
            $this->deductCoins($coins, $change);
            $machineStatus->resetBalance();
            $this->machineStatusRepository->save($machineStatus);
        });

        return new CoinsGetBackDTO(iterator_to_array($change));
    }

    private
    function findMachineStatusWithBalance(): MachineStatus
    {
        $machineStatus = $this->machineStatusRepository->find();
        if (empty($machineStatus) || $machineStatus->balance()->isEmpty()) {
            throw new MachineStatusBalanceIsEmpty();
        }

        return $machineStatus;
    }

    private
    function deductCoins(array $coins, CoinChange $change): void
    {
        $coinsByType = $this->groupCoinsByType($coins);

        foreach ($change as $typeValue => $unitsNeeded) {
            foreach ($coinsByType[$typeValue] ?? [] as $coin) {
                if ($unitsNeeded <= 0) {
                    break;
                }

                $quantity = $coin->quantity()->value();
                if ($quantity === 0) {
                    continue;
                }

                $unitsToTake = min($unitsNeeded, $quantity);
                $coin->decreaseQuantity(CoinQuantity::create($unitsToTake));
                $this->coinsRepository->save($coin);
                $unitsNeeded -= $unitsToTake;
            }
        }
    }

    /**
     * @param Coin[] $coins
     *
     * @return array<string, Coin[]>
     */
    private
    function groupCoinsByType(array $coins): array
    {
        $grouped = [];
        foreach ($coins as $coin) {
            $grouped[CoinType::fromValue($coin->value()->value())->value][] = $coin;
        }

        return $grouped;
    }
}
