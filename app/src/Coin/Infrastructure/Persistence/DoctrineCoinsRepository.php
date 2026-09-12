<?php

namespace App\Coin\Infrastructure\Persistence;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinsRepository;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineCriteriaConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CoinsRepository::class)]
final class DoctrineCoinsRepository implements CoinsRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(Coin $coin): void
    {
        $this->entityManager->persist($coin);
        $this->entityManager->flush();
    }

    public function searchByCriteria(Criteria $criteria): array
    {
        $doctrineCriteria = DoctrineCriteriaConverter::convert(
            $criteria,
            ['value' => 'value.value', 'quantity' => 'quantity.value']
        );

        return $this->entityManager->getRepository(Coin::class)
            ->matching($doctrineCriteria)
            ->toArray();
    }
}
