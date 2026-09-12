<?php

namespace App\Tests\Coin\Controllers;

use App\Coin\Domain\CoinsRepository;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CoinsInsertControllerTest extends WebTestCase
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

    public function testItInsertsACoinOnFirstRequest(): void
    {
        $this->insertCoin(0.10);

        $this->assertCoinsInserted();

        $coins = $this->coinsInDb();
        self::assertCount(1, $coins);
        self::assertSame(0.10, $coins[0]->value()->value());
        self::assertSame(1, $coins[0]->quantity()->value());
    }

    public function testItIncrementsQuantityWhenSameCoinIsInsertedAgain(): void
    {
        $this->insertCoin(0.25);
        $this->assertCoinsInserted();

        $this->insertCoin(0.25);
        $this->assertCoinsInserted();

        $coins = $this->coinsInDb();
        self::assertCount(1, $coins);
        self::assertSame(0.25, $coins[0]->value()->value());
        self::assertSame(2, $coins[0]->quantity()->value());
    }

    public function testItRejectsAnInvalidCoinTypeWith422(): void
    {
        $this->insertCoin(0.07);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Invalid coin value 0.07."}',
            $this->client->getResponse()->getContent()
        );
        self::assertSame([], $this->coinsInDb());
    }

    public function testItRejectsANegativeValueCoinWith422(): void
    {
        $this->insertCoin(-0.10);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Coin value can not be negative"}',
            $this->client->getResponse()->getContent()
        );
        self::assertSame([], $this->coinsInDb());
    }

    public function testItRejectsAMalformedPayloadWith422(): void
    {
        $this->client->request(
            'POST',
            '/insert_coin',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame([], $this->coinsInDb());
    }

    public function testItReturnsAnInternalServerErrorWhenTheRepositoryFails(): void
    {
        $repository = $this->createMock(CoinsRepository::class);
        $repository->expects(self::once())
            ->method('searchByCriteria')
            ->willThrowException(new \RuntimeException('Internal database failure'));

        self::getContainer()->set(CoinsRepository::class, $repository);

        $this->insertCoin(0.10);

        self::assertResponseStatusCodeSame(500);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Error inserting coin: Internal database failure"}',
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

    private function coinsInDb(): array
    {
        return self::getContainer()->get(CoinsRepository::class)
            ->searchByCriteria(new Criteria(Filters::none(), Order::none()));
    }

    private function assertCoinsInserted(): void
    {
        self::assertResponseStatusCodeSame(201);
        self::assertJsonStringEqualsJsonString(
            '{"message": "Coins Inserted"}',
            $this->client->getResponse()->getContent()
        );
    }
}
