<?php

namespace App\MachineStatus\Domain\Exceptions;

final class MachineStatusBalanceIsNotEnough extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('The current balance is not enough to purchase the selected item.');
    }
}
