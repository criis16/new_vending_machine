<?php

namespace App\MachineStatus\Domain;

final class MachineStatus
{
    public function __construct(
        private readonly MachineStatusId $id,
        private MachineStatusBalance     $balance
    )
    {
    }

    public static function create(MachineStatusId $id, MachineStatusBalance $balance): self
    {
        return new self($id, $balance);
    }

    public function id(): MachineStatusId
    {
        return $this->id;
    }

    public function balance(): MachineStatusBalance
    {
        return $this->balance;
    }

    public function credit(MachineStatusBalance $amount): void
    {
        $this->balance = $this->balance->add($amount);
    }

    public function resetBalance(): void
    {
        $this->balance = MachineStatusBalance::initialize();
    }
}
