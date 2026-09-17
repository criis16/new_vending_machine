<?php

namespace App\Tests\Item\Infrastructure\Persistence;

use App\Item\Domain\Item;
use App\Item\Domain\ItemId;
use App\Item\Domain\ItemName;
use App\Item\Domain\ItemPrice;
use App\Item\Domain\ItemQuantity;
use App\Item\Domain\ItemsRepository;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineItemsRepositoryTest extends KernelTestCase
{
    private static bool $schemaReady = false;

    private EntityManagerInterface $entityManager;
    private ItemsRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        if (!self::$schemaReady) {
            $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
            $schemaTool = new SchemaTool($this->entityManager);

            if (!$schemaManager->tablesExist(['items'])) {
                $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
            }

            self::$schemaReady = true;
        }

        $this->repository = self::getContainer()->get(ItemsRepository::class);
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

    private function createItem(string $name, int $quantity): Item
    {
        $itemName = ItemName::create($name);

        return Item::create(
            ItemId::generate(),
            $itemName,
            ItemQuantity::create($quantity),
            ItemPrice::createFromName($itemName)
        );
    }

    private function findWithFilter(string $field, string $operator, mixed $value): array
    {
        return $this->repository->searchByCriteria(new Criteria(
            Filters::fromValues([['field' => $field, 'operator' => $operator, 'value' => $value]]),
            Order::none()
        ));
    }

    public function testItSavesAndRetrievesAnItem(): void
    {
        $this->repository->save($this->createItem('water', 1));
        $this->entityManager->clear();

        $items = $this->repository->findAll();
        self::assertCount(1, $items);
        self::assertSame('Water', $items[0]->name()->value());
        self::assertSame(1, $items[0]->quantity()->value());
        self::assertSame(0.65, $items[0]->price()->value());
    }

    public function testItReturnsEmptyArrayWhenNoItemMatchesTheFilter(): void
    {
        $this->repository->save($this->createItem('Water', 1));

        self::assertSame([], $this->findWithFilter('value', '=', 'Soda'));
    }

    public function testItFiltersItemsByName(): void
    {
        $this->repository->save($this->createItem('Water', 1));
        $this->repository->save($this->createItem('Soda', 1));
        $this->entityManager->clear();

        $items = $this->findWithFilter('value', '=', 'Soda');
        self::assertCount(1, $items);
        self::assertSame('Soda', $items[0]->name()->value());
    }

    public function testItFiltersItemsByQuantity(): void
    {
        $this->repository->save($this->createItem('Water', 1));
        $this->repository->save($this->createItem('Juice', 3));
        $this->entityManager->clear();

        $items = $this->findWithFilter('quantity', '=', '3');
        self::assertCount(1, $items);
        self::assertSame(3, $items[0]->quantity()->value());
    }

    public function testItUpdatesQuantityWhenSavingTheSameItemAgain(): void
    {
        $this->repository->save($this->createItem('Water', 1));

        $items = $this->repository->findAll();
        $items[0]->increaseQuantity();
        $this->repository->save($items[0]);
        $this->entityManager->clear();

        $found = $this->repository->findAll();
        self::assertCount(1, $found);
        self::assertSame(2, $found[0]->quantity()->value());
    }
}
