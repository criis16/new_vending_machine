<?php

namespace App\Tests\MachineStatus\Domain;

use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\MachineStatus\Domain\MachineStatusId;
use PHPUnit\Framework\TestCase;

final class MachineStatusTest extends TestCase
{
    public function testItIsCreatedWithIdAndBalance(): void
    {
        $id = MachineStatusId::generate();
        $balance = MachineStatusBalance::initialize();

        $machineStatus = MachineStatus::create($id, $balance);

        self::assertSame($id, $machineStatus->id());
        self::assertSame($balance, $machineStatus->balance());
    }

    public function testItCreditsTheBalance(): void
    {
        $machineStatus = MachineStatus::create(
            MachineStatusId::generate(),
            MachineStatusBalance::initialize()
        );

        $machineStatus->credit(MachineStatusBalance::create(0.10));

        self::assertSame(0.10, $machineStatus->balance()->value());
    }

    public function testItAccumulatesCreditsRoundedToCents(): void
    {
        $machineStatus = MachineStatus::create(
            MachineStatusId::generate(),
            MachineStatusBalance::create(0.05)
        );

        $machineStatus->credit(MachineStatusBalance::create(0.10));

        self::assertSame(0.15, $machineStatus->balance()->value());
    }

    public function testItResetsTheBalance(): void
    {
        $machineStatus = MachineStatus::create(
            MachineStatusId::generate(),
            MachineStatusBalance::create(0.50)
        );

        $machineStatus->resetBalance();

        self::assertSame(0.00, $machineStatus->balance()->value());
    }
}
