<?php

namespace App\Tests\Item\Application;

use App\Coin\Application\CoinChangeRefund;
use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinChangeCalculator;
use App\Coin\Domain\CoinChangeDispenser;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinValue;
use App\Coin\Domain\Exceptions\NotEnoughCoinsToReturn;
use App\Item\Application\BuyItemDTO;
use App\Item\Application\BuyItemUseCase;
use App\Item\Application\ItemPurchasedDTO;
use App\Item\Domain\Exceptions\ItemNotAvailable;
use App\Item\Domain\Item;
use App\Item\Domain\ItemId;
use App\Item\Domain\ItemName;
use App\Item\Domain\ItemPrice;
use App\Item\Domain\ItemQuantity;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsEmpty;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsNotEnough;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\MachineStatus\Domain\MachineStatusId;
use App\Tests\Coin\Application\InMemoryCoinsRepository;
use App\Tests\MachineStatus\Application\InMemoryMachineStatusRepository;
use App\Tests\Shared\Application\InMemoryTransactionalService;
use PHPUnit\Framework\TestCase;

final class BuyItemUseCaseTest extends TestCase
{
    private BuyItemUseCase $useCase;
    private InMemoryItemsRepository $itemsRepository;
    private InMemoryMachineStatusRepository $machineStatusRepository;
    private InMemoryCoinsRepository $coinsRepository;

    protected function setUp(): void
    {
        $this->itemsRepository = new InMemoryItemsRepository();
        $this->machineStatusRepository = new InMemoryMachineStatusRepository();
        $this->coinsRepository = new InMemoryCoinsRepository();

        $this->useCase = new BuyItemUseCase(
            $this->itemsRepository,
            $this->machineStatusRepository,
            new CoinChangeRefund(
                $this->coinsRepository,
                $this->machineStatusRepository,
                new CoinChangeCalculator(),
                new CoinChangeDispenser()
            ),
            new InMemoryTransactionalService()
        );
    }

    private function givenItem(string $name, int $quantity, float $price): void
    {
        $this->itemsRepository->save(
            Item::create(
                ItemId::generate(),
                ItemName::create($name),
                ItemQuantity::create($quantity),
                ItemPrice::create($price)
            )
        );
    }

    private function givenCoin(float $value, int $quantity): Coin
    {
        $coin = Coin::create(
            CoinId::generate(),
            CoinValue::create($value),
            CoinQuantity::create($quantity)
        );

        $this->coinsRepository->save($coin);

        return $coin;
    }

    private function givenMachineStatusWithBalance(float $balance): MachineStatus
    {
        $machineStatus = MachineStatus::create(
            MachineStatusId::generate(),
            MachineStatusBalance::create($balance)
        );

        $this->machineStatusRepository->save($machineStatus);

        return $machineStatus;
    }

    private function buy(string $item): ItemPurchasedDTO
    {
        return $this->useCase->__invoke(new BuyItemDTO($item));
    }

    private function itemQuantity(string $name): int
    {
        foreach ($this->itemsRepository->findAll() as $item) {
            if ($item->name()->value() === $name) {
                return $item->quantity()->value();
            }
        }

        return -1;
    }

    private function coinQuantity(float $value): int
    {
        foreach ($this->coinsRepository->findAll() as $coin) {
            if ($coin->value()->value() === $value) {
                return $coin->quantity()->value();
            }
        }

        return -1;
    }

    public function testItDeliversTheItemAndReturnsTheChange(): void
    {
        $this->givenItem('water', 2, 0.65);
        $this->givenCoin(1.00, 1);
        $this->givenCoin(0.25, 1);
        $this->givenCoin(0.10, 1);
        $this->givenMachineStatusWithBalance(1.00);

        $dto = $this->buy('water');

        self::assertSame('Water', $dto->item);
        self::assertSame(['0.25' => 1, '0.10' => 1], $dto->coins);
        self::assertSame(1, $this->itemQuantity('Water'));
        self::assertSame(1, $this->coinQuantity(1.00));
        self::assertSame(0, $this->coinQuantity(0.25));
        self::assertSame(0, $this->coinQuantity(0.10));
        self::assertTrue($this->machineStatusRepository->find()->balance()->isEmpty());
    }

    public function testItDeliversTheItemWithExactPaymentAndNoChange(): void
    {
        $this->givenItem('water', 1, 0.65);
        $this->givenCoin(1.00, 1);
        $this->givenMachineStatusWithBalance(0.65);

        $dto = $this->buy('water');

        self::assertSame('Water', $dto->item);
        self::assertSame([], $dto->coins);
        self::assertSame(0, $this->itemQuantity('Water'));
        self::assertSame(1, $this->coinQuantity(1.00));
        self::assertTrue($this->machineStatusRepository->find()->balance()->isEmpty());
    }

    public function testItThrowsWhenTheBalanceIsNotEnough(): void
    {
        $this->givenItem('water', 2, 0.65);
        $this->givenMachineStatusWithBalance(0.30);

        try {
            $this->buy('water');
            self::fail('Expected MachineStatusBalanceIsNotEnough to be thrown.');
        } catch (MachineStatusBalanceIsNotEnough) {
        }

        self::assertSame(2, $this->itemQuantity('Water'));
        self::assertSame(0.30, $this->machineStatusRepository->find()->balance()->value());
    }

    public function testItThrowsWhenTheItemIsNotAvailable(): void
    {
        $this->givenItem('water', 0, 0.65);
        $this->givenCoin(0.25, 1);
        $this->givenCoin(0.10, 1);
        $this->givenMachineStatusWithBalance(1.00);

        try {
            $this->buy('water');
            self::fail('Expected ItemNotAvailable to be thrown.');
        } catch (ItemNotAvailable $exception) {
            self::assertSame('Item Water is not available.', $exception->getMessage());
        }

        self::assertSame(1.00, $this->machineStatusRepository->find()->balance()->value());
    }

    public function testItThrowsWhenTheBalanceIsEmpty(): void
    {
        $this->givenItem('water', 2, 0.65);

        try {
            $this->buy('water');
            self::fail('Expected MachineStatusBalanceIsEmpty to be thrown.');
        } catch (MachineStatusBalanceIsEmpty) {
        }

        self::assertSame(2, $this->itemQuantity('Water'));
    }

    public function testItThrowsWhenThereAreNotEnoughCoinsToReturnTheChange(): void
    {
        $this->givenItem('water', 2, 0.65);
        $this->givenCoin(1.00, 1);
        $this->givenMachineStatusWithBalance(1.00);

        try {
            $this->buy('water');
            self::fail('Expected NotEnoughCoinsToReturn to be thrown.');
        } catch (NotEnoughCoinsToReturn) {
        }

        // Nota: InMemoryTransactionalService no hace rollback; la atomicidad de la
        // compra se valida en BuyItemControllerTest (transacción real de Doctrine).
        self::assertSame(1, $this->coinQuantity(1.00));
        self::assertSame(1.00, $this->machineStatusRepository->find()->balance()->value());
    }
}
