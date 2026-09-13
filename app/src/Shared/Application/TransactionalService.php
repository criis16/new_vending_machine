<?php

namespace App\Shared\Application;

interface TransactionalService
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function execute(callable $operation): mixed;

}
