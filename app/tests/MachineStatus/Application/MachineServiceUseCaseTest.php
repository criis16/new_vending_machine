<?php

namespace App\Tests\MachineStatus\Application;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinValue;
use App\Coin\Domain\Exceptions\CoinNotValid;
use App\Item\Domain\Exceptions\ItemNotValid;
use App\Item\Domain\Item;
use App\Item\Domain\ItemId;
use App\Item\Domain\ItemName;
use App\Item\Domain\ItemPrice;
use App\Item\Domain\ItemQuantity;
use App\Item\Domain\ItemsRepository;
use App\MachineStatus\Application\MachineServiceUseCase;
use App\Shared\Application\TransactionalService;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use App\Tests\Coin\Application\InMemoryCoinsRepository;
use App\Tests\Item\Application\InMemoryItemsRepository;
use App\Tests\Shared\Application\InMemoryTransactionalService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MachineServiceUseCaseTest extends TestCase
{
    private MachineServiceUseCase $useCase;
    private CoinsRepository $coinsRepository;
    private ItemsRepository $itemsRepository;
    private TransactionalService $transactionalService;

    private CoinsRepository|MockObject $mockCoinsRepository;
    private ItemsRepository|MockObject $mockItemsRepository;
    private TransactionalService|MockObject $mockTransactionalService;
    private MachineServiceUseCase $mockUseCase;

    protected function setUp(): void
    {
        $this->coinsRepository = new InMemoryCoinsRepository();
        $this->itemsRepository = new InMemoryItemsRepository();
        $this->transactionalService = new InMemoryTransactionalService();
        $this->useCase = new MachineServiceUseCase(
            $this->coinsRepository,
            $this->itemsRepository,
            $this->transactionalService
        );
    }

    private function mockSetUp(): void
    {
        $this->mockCoinsRepository = $this->createMock(CoinsRepository::class);
        $this->mockItemsRepository = $this->createMock(ItemsRepository::class);
        $this->mockTransactionalService = $this->createMock(TransactionalService::class);
        $this->mockUseCase = new MachineServiceUseCase(
            $this->mockCoinsRepository,
            $this->mockItemsRepository,
            $this->mockTransactionalService
        );
    }

    private function anyCriteria(): Criteria
    {
        return new Criteria(Filters::none(), Order::none());
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

    public function testItCreatesAndSavesANewCoinWhenNoneExists(): void
    {
        $this->useCase->__invoke(['0.10' => 1], []);

        $coins = $this->coinsRepository->searchByCriteria($this->anyCriteria());
        self::assertCount(1, $coins);
        self::assertInstanceOf(Coin::class, $coins[0]);
        self::assertSame(0.10, $coins[0]->value()->value());
        self::assertSame(1, $coins[0]->quantity()->value());

        self::assertSame([], $this->itemsRepository->findAll());
    }

    public function testItCreatesAndSavesANewItemWhenNoneExists(): void
    {
        $this->useCase->__invoke([], ['water' => 1]);

        $items = $this->itemsRepository->searchByCriteria($this->anyCriteria());
        self::assertCount(1, $items);
        self::assertInstanceOf(Item::class, $items[0]);
        self::assertSame('Water', $items[0]->name()->value());
        self::assertSame(1, $items[0]->quantity()->value());
        self::assertSame(0.65, $items[0]->price()->value());

        self::assertSame([], $this->coinsRepository->findAll());
    }

    public function testItIncrementsQuantityWhenCoinAlreadyExists(): void
    {
        $this->coinsRepository->save(Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize()));

        $this->useCase->__invoke(['0.10' => 1], []);

        $coins = $this->coinsRepository->searchByCriteria($this->anyCriteria());
        self::assertCount(1, $coins);
        self::assertSame(0.10, $coins[0]->value()->value());
        self::assertSame(2, $coins[0]->quantity()->value());
    }

    public function testItIncrementsQuantityWhenItemAlreadyExists(): void
    {
        $this->itemsRepository->save($this->createItem('Water', 1));

        $this->useCase->__invoke([], ['WATER' => 2]);

        $items = $this->itemsRepository->searchByCriteria($this->anyCriteria());
        self::assertCount(1, $items);
        self::assertSame('Water', $items[0]->name()->value());
        self::assertSame(3, $items[0]->quantity()->value());
    }

    public function testItStocksACoinAndAnItemTogether(): void
    {
        $this->useCase->__invoke(['0.25' => 2], ['soda' => 1]);

        $coins = $this->coinsRepository->findAll();
        self::assertCount(1, $coins);
        self::assertSame(0.25, $coins[0]->value()->value());
        self::assertSame(2, $coins[0]->quantity()->value());

        $items = $this->itemsRepository->findAll();
        self::assertCount(1, $items);
        self::assertSame('Soda', $items[0]->name()->value());
        self::assertSame(1.50, $items[0]->price()->value());
    }

    public function testItThrowsCoinNotValidWithoutSaving(): void
    {
        try {
            $this->useCase->__invoke(['0.20' => 1], []);
            self::fail('Expected CoinNotValid to be thrown.');
        } catch (CoinNotValid) {
        }

        self::assertSame([], $this->coinsRepository->findAll());
        self::assertSame([], $this->itemsRepository->findAll());
    }

    public function testItThrowsItemNotValidWithoutSaving(): void
    {
        try {
            $this->useCase->__invoke([], ['Chips' => 1]);
            self::fail('Expected ItemNotValid to be thrown.');
        } catch (ItemNotValid) {
        }

        self::assertSame([], $this->coinsRepository->findAll());
        self::assertSame([], $this->itemsRepository->findAll());
    }

    public function testItSavesTheExactFoundItemInstanceOnIncrement(): void
    {
        $this->mockSetUp();

        $this->mockTransactionalService->expects($this->once())
            ->method('execute')
            ->willReturnCallback(static fn(callable $operation): mixed => $operation());

        $existing = $this->createItem('Water', 1);

        $this->mockItemsRepository->expects($this->once())
            ->method('searchByCriteria')
            ->willReturn([$existing]);
        $this->mockItemsRepository->expects($this->once())
            ->method('save')
            ->with($existing);
        $this->mockCoinsRepository->expects($this->never())->method('searchByCriteria');

        $this->mockUseCase->__invoke([], ['WATER' => 2]);

        self::assertSame(3, $existing->quantity()->value());
    }

    public function testItSavesANewItemMatchingNameQuantityAndPrice(): void
    {
        $this->mockSetUp();

        $this->mockTransactionalService->expects($this->once())
            ->method('execute')
            ->willReturnCallback(static fn(callable $operation): mixed => $operation());

        $this->mockItemsRepository->expects($this->once())
            ->method('searchByCriteria')
            ->willReturn([]);
        $this->mockItemsRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn(Item $item): bool => $item->name()->value() === 'Water'
                    && $item->quantity()->value() === 1
                    && $item->price()->value() === 0.65
                    && $item->id() instanceof ItemId
            ));
        $this->mockCoinsRepository->expects($this->never())->method('searchByCriteria');

        $this->mockUseCase->__invoke([], ['water' => 1]);
    }
}
