<?php

namespace App\Tests\Coin\Application;

use App\Coin\Application\CoinInsertUseCase;
use App\Coin\Application\CoinsInsertDTO;
use App\Coin\Domain\CoinsRepository;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusRepository;
use App\Shared\Application\TransactionalService;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CoinInsertUseCaseTransactionalTest extends KernelTestCase
{
    private static bool $schemaReady = false;

    private EntityManagerInterface $entityManager;
    private CoinsRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        if (!self::$schemaReady) {
            $schemaTool = new SchemaTool($this->entityManager);

            if (!$this->entityManager->getConnection()->createSchemaManager()->tablesExist(['coins'])) {
                $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
            }

            self::$schemaReady = true;
        }

        $this->repository = self::getContainer()->get(CoinsRepository::class);
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

    public function testItRollsBackTheCoinInsertionWhenTheMachineStatusSaveFails(): void
    {
        $failingStatus = new class(self::getContainer()->get(MachineStatusRepository::class))
            implements MachineStatusRepository {
            public function __construct(private readonly MachineStatusRepository $inner)
            {
            }

            public function save(MachineStatus $machineStatus): void
            {
                throw new \RuntimeException('Machine status save failed');
            }

            public function find(): ?MachineStatus
            {
                return $this->inner->find();
            }
        };

        $useCase = new CoinInsertUseCase(
            $this->repository,
            $failingStatus,
            self::getContainer()->get(TransactionalService::class)
        );

        try {
            $useCase->__invoke(new CoinsInsertDTO(0.10));
            self::fail('Expected a RuntimeException when the machine status save fails.');
        } catch (\RuntimeException) {
            // expected: the second save fails inside the transaction
        }

        self::bootKernel();
        $freshRepository = self::getContainer()->get(CoinsRepository::class);

        self::assertSame(
            [],
            $freshRepository->searchByCriteria(new Criteria(Filters::none(), Order::none()))
        );
    }
}
