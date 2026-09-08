<?php

namespace App\Coin\Infrastructure\Persistence;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinsRespository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CoinsRespository::class)]
final class DoctrineCoinsRepository implements CoinsRespository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(Coin $coin): void
    {
        $this->entityManager->persist($coin);
        $this->entityManager->flush();
    }
}
