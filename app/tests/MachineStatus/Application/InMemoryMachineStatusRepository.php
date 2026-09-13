<?php

namespace App\Tests\MachineStatus\Application;

use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusRepository;

final class InMemoryMachineStatusRepository implements MachineStatusRepository
{
    /** @var MachineStatus[] */
    private array $machineStatus = [];

    public function save(MachineStatus $machineStatus): void
    {
        $this->machineStatus[$machineStatus->id()->value()] = $machineStatus;
    }

    public function find(): ?MachineStatus
    {
        return empty($this->machineStatus) ? null : reset($this->machineStatus);
    }
}
