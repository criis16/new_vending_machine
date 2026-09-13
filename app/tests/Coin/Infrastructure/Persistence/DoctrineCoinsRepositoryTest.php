<?php

namespace App\Tests\Coin\Infrastructure\Persistence;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinValue;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineCoinsRepositoryTest extends KernelTestCase
{
    private static bool $schemaReady = false;

    private EntityManagerInterface $entityManager;
    private CoinsRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        if (!self::$schemaReady) {
            $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
            $schemaTool = new SchemaTool($this->entityManager);

            if (!$schemaManager->tablesExist(['coins'])) {
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

    private function findAll(): array
    {
        return $this->repository->searchByCriteria(new Criteria(Filters::none(), Order::none()));
    }

    private function findWithFilter(string $field, string $operator, mixed $value): array
    {
        return $this->repository->searchByCriteria(new Criteria(
            Filters::fromValues([['field' => $field, 'operator' => $operator, 'value' => $value]]),
            Order::none()
        ));
    }

    public function testItSavesAndRetrievesACoin(): void
    {
        $coin = Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize());
        $this->repository->save($coin);
        $this->entityManager->clear();

        $coins = $this->findAll();
        self::assertCount(1, $coins);
        self::assertSame(0.10, $coins[0]->value()->value());
        self::assertSame(1, $coins[0]->quantity()->value());
    }

    public function testItReturnsEmptyArrayWhenNoCoinMatchesTheFilter(): void
    {
        $this->repository->save(Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize()));

        self::assertSame([], $this->findWithFilter('value', '=', '1.00'));
    }

    public function testItFiltersCoinsByValue(): void
    {
        $this->repository->save(Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize()));
        $this->repository->save(Coin::create(CoinId::generate(), CoinValue::create(0.25), CoinQuantity::initialize()));
        $this->entityManager->clear();

        $coins = $this->findWithFilter('value', '=', '0.25');
        self::assertCount(1, $coins);
        self::assertSame(0.25, $coins[0]->value()->value());
    }

    public function testItFiltersCoinsByQuantity(): void
    {
        $this->repository->save(Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::create(1)));
        $this->repository->save(Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::create(3)));
        $this->entityManager->clear();

        $coins = $this->findWithFilter('quantity', '=', '3');
        self::assertCount(1, $coins);
        self::assertSame(3, $coins[0]->quantity()->value());
    }

    public function testItUpdatesQuantityWhenSavingTheSameCoinAgain(): void
    {
        $coin = Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize());
        $this->repository->save($coin);

        $coin->increaseQuantity();
        $this->repository->save($coin);
        $this->entityManager->clear();

        $coins = $this->findAll();
        self::assertCount(1, $coins);
        self::assertSame(2, $coins[0]->quantity()->value());
    }
}
