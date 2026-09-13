<?php

namespace App\Tests\MachineStatus\Infrastructure\Persistence;

use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\MachineStatus\Domain\MachineStatusId;
use App\MachineStatus\Domain\MachineStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineMachineStatusRepositoryTest extends KernelTestCase
{
    private static bool $schemaReady = false;

    private EntityManagerInterface $entityManager;
    private MachineStatusRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        if (!self::$schemaReady) {
            $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
            $schemaTool = new SchemaTool($this->entityManager);

            if (!$schemaManager->tablesExist(['machine_status'])) {
                $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
            }

            self::$schemaReady = true;
        }

        $this->repository = self::getContainer()->get(MachineStatusRepository::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        $this->entityManager->close();
        parent::tearDown();
    }

    public function testItSavesAndRetrievesAMachineStatus(): void
    {
        $status = MachineStatus::create(
            MachineStatusId::generate(),
            MachineStatusBalance::create(0.10)
        );
        $this->repository->save($status);
        $this->entityManager->clear();

        $found = $this->repository->find();

        self::assertNotNull($found);
        self::assertSame($status->id()->value(), $found->id()->value());
        self::assertSame(0.10, $found->balance()->value());
    }

    public function testItReturnsNullWhenNoStatusExists(): void
    {
        self::assertNull($this->repository->find());
    }

    public function testItUpdatesTheBalanceWhenSavingTheSameStatusAgain(): void
    {
        $status = MachineStatus::create(
            MachineStatusId::generate(),
            MachineStatusBalance::initialize()
        );
        $this->repository->save($status);

        $status->credit(MachineStatusBalance::create(0.10));
        $this->repository->save($status);
        $this->entityManager->clear();

        $found = $this->repository->find();
        self::assertNotNull($found);
        self::assertSame(0.10, $found->balance()->value());
    }
}
