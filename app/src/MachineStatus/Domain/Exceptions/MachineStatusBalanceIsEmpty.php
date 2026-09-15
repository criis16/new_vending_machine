<?php

namespace App\MachineStatus\Domain\Exceptions;

final class MachineStatusBalanceIsEmpty extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('The current balance is empty. Please insert coins first.');
    }
}
