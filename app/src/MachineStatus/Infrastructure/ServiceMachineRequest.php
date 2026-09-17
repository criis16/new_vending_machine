<?php

namespace App\MachineStatus\Infrastructure;

use Symfony\Component\Validator\Constraints as Assert;

final class ServiceMachineRequest
{
    public function __construct(
        /** @var array<string, int> */
        #[Assert\NotBlank]
        #[Assert\All([new Assert\Type('int')])]
        public array $coins,

        /** @var array<string, int> */
        #[Assert\NotBlank]
        #[Assert\All([new Assert\Type('int')])]
        public array $items,
    )
    {
    }

}
