<?php

namespace App\MachineStatus\Infrastructure\Persistence\Doctrine;

use App\MachineStatus\Domain\MachineStatusId;
use App\Shared\Infrastructure\Persistence\Doctrine\UuidType;

final class MachineStatusIdType extends UuidType
{
    protected function typeClassName(): string
    {
        return MachineStatusId::class;
    }
}
