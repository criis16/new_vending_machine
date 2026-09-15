<?php

namespace App\Tests\Coin\Controllers;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinValue;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\MachineStatus\Domain\MachineStatusId;
use App\MachineStatus\Domain\MachineStatusRepository;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CoinsGetBackControllerTest extends WebTestCase
{
    private static bool $schemaReady = false;
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        if (!self::$schemaReady) {
            $schemaTool = new SchemaTool($this->entityManager);

            if (!$this->entityManager->getConnection()->createSchemaManager()->tablesExist(['coins'])) {
                $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
            }

            self::$schemaReady = true;
        }

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

    public function testItReturnsTheInsertedCoinsAndResetsTheBalance(): void
    {
        $this->insertCoin(0.10);

        $this->returnCoins();

        self::assertResponseStatusCodeSame(200);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Coins returned successfully", "coins": {"0.10": 1}}',
            $this->client->getResponse()->getContent()
        );

        $coins = $this->coinsInDb();
        self::assertCount(1, $coins);
        self::assertSame(0, $coins[0]->quantity()->value());
        self::assertTrue($this->machineStatus()->isEmpty());
    }

    public function testItReturnsTheExactCombinationAcrossDenominations(): void
    {
        $this->seedCoin(0.25, 1);
        $this->seedCoin(0.10, 2);
        $this->seedCoin(0.05, 1);
        $this->seedMachineStatus(0.50);

        $this->returnCoins();

        self::assertResponseStatusCodeSame(200);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Coins returned successfully", "coins": {"0.25": 1, "0.10": 2, "0.05": 1}}',
            $this->client->getResponse()->getContent()
        );
        self::assertTrue($this->machineStatus()->isEmpty());
    }

    public function testItReturnsChangeAcrossMixedTypesAndDeductsExactQuantity(): void
    {
        $this->seedCoin(0.25, 1);
        $this->seedCoin(0.05, 2);
        $this->seedMachineStatus(0.30);

        $this->returnCoins();

        self::assertResponseStatusCodeSame(200);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Coins returned successfully", "coins": {"0.25": 1, "0.05": 1}}',
            $this->client->getResponse()->getContent()
        );

        $coins = $this->coinsInDb();
        self::assertCount(2, $coins);
        $nickel = current(array_filter(
            $coins,
            fn(Coin $coin): bool => $coin->value()->value() === 0.05
        ));
        self::assertSame(1, $nickel->quantity()->value());
        self::assertTrue($this->machineStatus()->isEmpty());
    }

    public function testItReturns422WhenTheBalanceIsEmpty(): void
    {
        $this->returnCoins();

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "The current balance is empty. Please insert coins first."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testItReturns422WhenThereAreNotEnoughCoinsToReturn(): void
    {
        $this->seedCoin(0.10, 2);
        $this->seedMachineStatus(1.00);

        $this->returnCoins();

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "There are not enough coins to return the current balance."}',
            $this->client->getResponse()->getContent()
        );

        self::assertSame(1.00, $this->machineStatus()->value());
    }

    public function testItReturnsAnInternalServerErrorWhenTheRepositoryFails(): void
    {
        $repository = $this->createMock(CoinsRepository::class);
        $repository->expects(self::once())
            ->method('findAll')
            ->willThrowException(new \RuntimeException('Internal database failure'));

        self::getContainer()->set(CoinsRepository::class, $repository);

        $this->seedMachineStatus(0.10);

        $this->returnCoins();

        self::assertResponseStatusCodeSame(500);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Error returning coins: Internal database failure"}',
            $this->client->getResponse()->getContent()
        );
    }

    private function insertCoin(float $value): void
    {
        $this->client->request(
            'POST',
            '/insert_coin',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['coin' => $value])
        );
    }

    private function returnCoins(): void
    {
        $this->client->request('GET', '/return_coins');
    }

    private function seedCoin(float $value, int $quantity): void
    {
        $this->entityManager->persist(
            Coin::create(
                CoinId::generate(),
                CoinValue::create($value),
                CoinQuantity::create($quantity)
            )
        );
        $this->entityManager->flush();
    }

    private function seedMachineStatus(float $balance): void
    {
        $this->entityManager->persist(
            MachineStatus::create(
                MachineStatusId::generate(),
                MachineStatusBalance::create($balance)
            )
        );
        $this->entityManager->flush();
    }

    private function coinsInDb(): array
    {
        return self::getContainer()->get(CoinsRepository::class)
            ->searchByCriteria(new Criteria(Filters::none(), Order::none()));
    }

    private function machineStatus(): MachineStatusBalance
    {
        /** @var MachineStatus $machineStatus */
        $machineStatus = self::getContainer()->get(MachineStatusRepository::class)->find();

        return $machineStatus->balance();
    }
}
