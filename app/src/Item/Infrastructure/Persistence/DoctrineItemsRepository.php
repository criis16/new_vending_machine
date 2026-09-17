<?php

namespace App\Item\Infrastructure\Persistence;

use App\Item\Domain\Item;
use App\Item\Domain\ItemsRepository;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineCriteriaConverter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ItemsRepository::class)]
final class DoctrineItemsRepository implements ItemsRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function save(Item $item): void
    {
        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function searchByCriteria(Criteria $criteria): array
    {
        $doctrineCriteria = DoctrineCriteriaConverter::convert(
            $criteria,
            ['value' => 'name.value', 'quantity' => 'quantity.value']
        );

        return $this->entityManager->getRepository(Item::class)
            ->matching($doctrineCriteria)
            ->toArray();
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Item::class)->findAll();
    }
}
