<?php

namespace App\MachineStatus\Domain\Exceptions;

final class MachineStatusBalanceCannotBeNegative extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('Machine status balance can not be negative');
    }
}
