<?php

namespace App\Tests\Item\Controllers;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinValue;
use App\Item\Domain\Item;
use App\Item\Domain\ItemId;
use App\Item\Domain\ItemName;
use App\Item\Domain\ItemPrice;
use App\Item\Domain\ItemQuantity;
use App\Item\Domain\ItemsRepository;
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

final class BuyItemControllerTest extends WebTestCase
{
    private static bool $schemaReady = false;
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = BuyItemControllerTest::createClient();
        $this->client->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        if (!self::$schemaReady) {
            $schemaTool = new SchemaTool($this->entityManager);

            if (!$this->entityManager->getConnection()->createSchemaManager()->tablesExist(['coins', 'items'])) {
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

    public function testItDeliversTheItemAndReturnsTheChange(): void
    {
        $this->seedItem('water', 2);
        $this->seedCoin(1.00, 1);
        $this->seedCoin(0.25, 1);
        $this->seedCoin(0.10, 1);
        $this->seedMachineStatus(1.00);

        $this->buy('water');

        self::assertResponseStatusCodeSame(200);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Item delivered", "item": "Water", "change": {"0.25": 1, "0.10": 1}}',
            $this->client->getResponse()->getContent()
        );

        $items = $this->itemsInDb();
        self::assertCount(1, $items);
        self::assertSame(1, $items[0]->quantity()->value());
        self::assertTrue($this->machineStatus()->isEmpty());
    }

    public function testItDeliversTheItemWithExactPaymentAndNoChange(): void
    {
        $this->seedItem('water', 1);
        $this->seedCoin(1.00, 1);
        $this->seedMachineStatus(0.65);

        $this->buy('water');

        self::assertResponseStatusCodeSame(200);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Item delivered", "item": "Water", "change": []}',
            $this->client->getResponse()->getContent()
        );

        self::assertSame(0, $this->itemsInDb()[0]->quantity()->value());
        self::assertTrue($this->machineStatus()->isEmpty());
    }

    public function testItReturns422WhenTheItemIsNotAvailable(): void
    {
        $this->seedItem('water', 0);
        $this->seedCoin(0.25, 1);
        $this->seedCoin(0.10, 1);
        $this->seedMachineStatus(1.00);

        $this->buy('water');

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Item Water is not available."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testItReturns422WhenTheItemIsNotValid(): void
    {
        $this->buy('chips');

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Invalid item chips."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testItReturns422WhenTheBalanceIsEmpty(): void
    {
        $this->seedItem('water', 2);

        $this->buy('water');

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "The current balance is empty. Please insert coins first."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testItReturns422WhenTheBalanceIsNotEnough(): void
    {
        $this->seedItem('water', 2);
        $this->seedMachineStatus(0.30);

        $this->buy('water');

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "The current balance is not enough to purchase the selected item."}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testItReturns422WhenThereAreNotEnoughCoinsAndRollsBackThePurchase(): void
    {
        $this->seedItem('water', 2);
        $this->seedCoin(1.00, 1);
        $this->seedMachineStatus(1.00);

        $this->buy('water');

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "There are not enough coins to return the current balance."}',
            $this->client->getResponse()->getContent()
        );

        $this->entityManager->clear();

        self::assertSame(2, $this->itemsInDb()[0]->quantity()->value());
        self::assertSame(1.00, $this->machineStatus()->value());
    }

    public function testItReturnsAnInternalServerErrorWhenTheRepositoryFails(): void
    {
        $repository = $this->createMock(ItemsRepository::class);
        $repository->expects(self::once())
            ->method('searchByCriteria')
            ->willThrowException(new \RuntimeException('Internal database failure'));

        self::getContainer()->set(ItemsRepository::class, $repository);

        $this->buy('water');

        self::assertResponseStatusCodeSame(500);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Error buying item: Internal database failure"}',
            $this->client->getResponse()->getContent()
        );
    }

    public function testItReturns404WhenTheItemNameIsBlank(): void
    {
        $this->client->request('GET', '/item', ['name' => '']);

        self::assertResponseStatusCodeSame(404);
    }

    private function buy(string $item): void
    {
        $this->client->request('GET', '/item', ['name' => $item]);
    }

    private function seedItem(string $name, int $quantity): void
    {
        $itemName = ItemName::create($name);

        $this->entityManager->persist(
            Item::create(
                ItemId::generate(),
                $itemName,
                ItemQuantity::create($quantity),
                ItemPrice::createFromName($itemName)
            )
        );
        $this->entityManager->flush();
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

    private function itemsInDb(): array
    {
        return self::getContainer()->get(ItemsRepository::class)
            ->searchByCriteria(new Criteria(Filters::none(), Order::none()));
    }

    private function machineStatus(): MachineStatusBalance
    {
        /** @var MachineStatus $machineStatus */
        $machineStatus = self::getContainer()->get(MachineStatusRepository::class)->find();

        return $machineStatus->balance();
    }
}
