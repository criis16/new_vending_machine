<?php

namespace App\Tests\MachineStatus\Controllers;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinsRepository;
use App\Item\Domain\Item;
use App\Item\Domain\ItemsRepository;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ServiceMachineControllerTest extends WebTestCase
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

    public function testItServicesTheMachineWithCoinsAndItems(): void
    {
        $this->serviceMachine(['0.10' => 1, '0.25' => 2], ['water' => 2, 'soda' => 1]);

        self::assertResponseStatusCodeSame(200);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Machine serviced"}',
            $this->client->getResponse()->getContent()
        );

        $coins = $this->coinsInDb();
        self::assertCount(2, $coins);
        $quarter = current(array_filter($coins, fn(Coin $c): bool => $c->value()->value() === 0.25));
        $dime = current(array_filter($coins, fn(Coin $c): bool => $c->value()->value() === 0.10));
        self::assertSame(2, $quarter->quantity()->value());
        self::assertSame(1, $dime->quantity()->value());

        $items = $this->itemsInDb();
        self::assertCount(2, $items);
        $soda = current(array_filter($items, fn(Item $i): bool => $i->name()->value() === 'Soda'));
        $water = current(array_filter($items, fn(Item $i): bool => $i->name()->value() === 'Water'));
        self::assertSame(1, $soda->quantity()->value());
        self::assertSame(1.50, $soda->price()->value());
        self::assertSame(2, $water->quantity()->value());
    }

    public function testItIncrementsQuantityWhenTheSameCoinAndItemAreServicedAgain(): void
    {
        $this->serviceMachine(['0.10' => 1], ['water' => 1]);
        self::assertResponseStatusCodeSame(200);

        $this->serviceMachine(['0.10' => 1], ['water' => 1]);
        self::assertResponseStatusCodeSame(200);

        $coins = $this->coinsInDb();
        self::assertCount(1, $coins);
        self::assertSame(2, $coins[0]->quantity()->value());

        $items = $this->itemsInDb();
        self::assertCount(1, $items);
        self::assertSame(2, $items[0]->quantity()->value());
    }

    public function testItRejectsAnInvalidCoinValueWith422(): void
    {
        $this->serviceMachine(['0.07' => 1], ['water' => 1]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Invalid coin value 0.07."}',
            $this->client->getResponse()->getContent()
        );
        self::assertSame([], $this->coinsInDb());
        self::assertSame([], $this->itemsInDb());
    }

    public function testItRejectsANegativeCoinValueWith422(): void
    {
        $this->serviceMachine(['-0.10' => 1], ['water' => 1]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Coin value can not be negative"}',
            $this->client->getResponse()->getContent()
        );
        self::assertSame([], $this->coinsInDb());
        self::assertSame([], $this->itemsInDb());
    }

    public function testItRejectsANegativeCoinQuantityWith422(): void
    {
        $this->serviceMachine(['0.10' => -1], ['water' => 1]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Coin quantity can not be negative"}',
            $this->client->getResponse()->getContent()
        );
        self::assertSame([], $this->coinsInDb());
        self::assertSame([], $this->itemsInDb());
    }

    public function testItRejectsAnInvalidItemWith422(): void
    {
        $this->serviceMachine(['0.10' => 1], ['Chips' => 1]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Invalid item Chips."}',
            $this->client->getResponse()->getContent()
        );
        self::assertSame([], $this->itemsInDb());
    }

    public function testItRejectsANegativeItemQuantityWith422(): void
    {
        $this->serviceMachine(['0.10' => 1], ['water' => -1]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Item quantity can not be negative"}',
            $this->client->getResponse()->getContent()
        );
        self::assertSame([], $this->itemsInDb());
    }

    public function testItRejectsAMalformedPayloadWith422(): void
    {
        $this->client->request(
            'POST',
            '/service',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame([], $this->coinsInDb());
        self::assertSame([], $this->itemsInDb());
    }

    public function testItReturnsAnInternalServerErrorWhenTheRepositoryFails(): void
    {
        $repository = $this->createMock(CoinsRepository::class);
        $repository->expects(self::once())
            ->method('searchByCriteria')
            ->willThrowException(new \RuntimeException('Internal database failure'));

        self::getContainer()->set(CoinsRepository::class, $repository);

        $this->serviceMachine(['0.10' => 1], ['water' => 1]);

        self::assertResponseStatusCodeSame(500);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Error servicing machine: Internal database failure"}',
            $this->client->getResponse()->getContent()
        );
    }

    private function serviceMachine(array $coins, array $items): void
    {
        $this->client->request(
            'POST',
            '/service',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['coins' => $coins, 'items' => $items])
        );
    }

    private function coinsInDb(): array
    {
        return self::getContainer()->get(CoinsRepository::class)
            ->searchByCriteria(new Criteria(Filters::none(), Order::none()));
    }

    private function itemsInDb(): array
    {
        return self::getContainer()->get(ItemsRepository::class)
            ->searchByCriteria(new Criteria(Filters::none(), Order::none()));
    }
}
