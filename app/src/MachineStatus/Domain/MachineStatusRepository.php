<?php

namespace App\MachineStatus\Domain;

interface MachineStatusRepository
{
    public function save(MachineStatus $machineStatus): void;

    public function find(): ?MachineStatus;
}
