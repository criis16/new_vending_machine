<?php

namespace App\Tests\Shared\Application;

use App\Shared\Application\TransactionalService;

final class InMemoryTransactionalService implements TransactionalService
{
    public function execute(callable $operation): mixed
    {
        return $operation();
    }
}
