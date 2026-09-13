<?php

namespace App\MachineStatus\Infrastructure\Persistence;

use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MachineStatusRepository::class)]
final readonly class DoctrineMachineStatusRepository implements MachineStatusRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(MachineStatus $machineStatus): void
    {
        $this->entityManager->persist($machineStatus);
        $this->entityManager->flush();
    }

    public function find(): ?MachineStatus
    {
        return $this->entityManager->getRepository(MachineStatus::class)->findOneBy([]);
    }
}
