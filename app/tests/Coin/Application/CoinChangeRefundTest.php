<?php

namespace App\Tests\Coin\Application;

use App\Coin\Application\CoinChangeRefund;
use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinChangeCalculator;
use App\Coin\Domain\CoinChangeDispenser;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinValue;
use App\Coin\Domain\Exceptions\NotEnoughCoinsToReturn;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\MachineStatus\Domain\MachineStatusId;
use App\Tests\MachineStatus\Application\InMemoryMachineStatusRepository;
use PHPUnit\Framework\TestCase;

final class CoinChangeRefundTest extends TestCase
{
    private CoinChangeRefund $refund;
    private InMemoryCoinsRepository $coinsRepository;
    private InMemoryMachineStatusRepository $machineStatusRepository;

    protected function setUp(): void
    {
        $this->coinsRepository = new InMemoryCoinsRepository();
        $this->machineStatusRepository = new InMemoryMachineStatusRepository();

        $this->refund = new CoinChangeRefund(
            $this->coinsRepository,
            $this->machineStatusRepository,
            new CoinChangeCalculator(),
            new CoinChangeDispenser()
        );
    }

    private function givenCoin(float $value, int $quantity): Coin
    {
        $coin = Coin::create(
            CoinId::generate(),
            CoinValue::create($value),
            CoinQuantity::create($quantity)
        );

        $this->coinsRepository->save($coin);

        return $coin;
    }

    private function givenMachineStatus(float $balance): MachineStatus
    {
        return MachineStatus::create(
            MachineStatusId::generate(),
            MachineStatusBalance::create($balance)
        );
    }

    public function testItRefundsTheChangePersistingModifiedCoinsAndResettingTheBalance(): void
    {
        $dime = $this->givenCoin(0.10, 3);
        $machineStatus = $this->givenMachineStatus(0.10);

        $change = $this->refund->refund($machineStatus, 0.10);

        self::assertSame(['0.10' => 1], iterator_to_array($change));
        self::assertSame(2, $dime->quantity()->value());
        self::assertTrue($machineStatus->balance()->isEmpty());
    }

    public function testItPersistsEveryDispensedCoinAcrossDenominations(): void
    {
        $quarter = $this->givenCoin(0.25, 1);
        $dime = $this->givenCoin(0.10, 1);
        $machineStatus = $this->givenMachineStatus(0.35);

        $change = $this->refund->refund($machineStatus, 0.35);

        self::assertSame(['0.25' => 1, '0.10' => 1], iterator_to_array($change));
        self::assertSame(0, $quarter->quantity()->value());
        self::assertSame(0, $dime->quantity()->value());
    }

    public function testItDoesNotPersistAnythingWhenTheChangeCannotBeResolved(): void
    {
        $dime = $this->givenCoin(0.10, 2);
        $machineStatus = $this->givenMachineStatus(1.00);

        $this->expectException(NotEnoughCoinsToReturn::class);

        $this->refund->refund($machineStatus, 1.00);

        self::assertSame(2, $dime->quantity()->value());
        self::assertFalse($machineStatus->balance()->isEmpty());
    }
}
