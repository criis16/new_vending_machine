<?php

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Application\TransactionalService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionalService implements TransactionalService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function execute(callable $operation): mixed
    {
        return $this->entityManager->wrapInTransaction($operation);
    }
}
