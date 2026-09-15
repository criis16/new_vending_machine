<?php

namespace App\Tests\Coin\Application;

use App\Coin\Application\CoinsGetBackDTO;
use App\Coin\Application\CoinsGetBackUseCase;
use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinChangeCalculator;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinValue;
use App\Coin\Domain\Exceptions\NotEnoughCoinsToReturn;
use App\MachineStatus\Domain\Exceptions\MachineStatusBalanceIsEmpty;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\MachineStatus\Domain\MachineStatusId;
use App\MachineStatus\Domain\MachineStatusRepository;
use App\Shared\Application\TransactionalService;
use App\Tests\MachineStatus\Application\InMemoryMachineStatusRepository;
use App\Tests\Shared\Application\InMemoryTransactionalService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CoinsGetBackUseCaseTest extends TestCase
{
    private CoinsGetBackUseCase $useCase;
    private CoinsRepository $coinsRepository;
    private MachineStatusRepository $machineStatusRepository;
    private TransactionalService $transactionalService;

    private CoinsRepository|MockObject $mockCoinsRepository;
    private MachineStatusRepository|MockObject $mockMachineStatusRepository;
    private TransactionalService|MockObject $mockTransactionalService;
    private CoinsGetBackUseCase $mockUseCase;

    protected function setUp(): void
    {
        $this->coinsRepository = new InMemoryCoinsRepository();
        $this->machineStatusRepository = new InMemoryMachineStatusRepository();
        $this->transactionalService = new InMemoryTransactionalService();

        $this->useCase = new CoinsGetBackUseCase(
            $this->coinsRepository,
            $this->machineStatusRepository,
            new CoinChangeCalculator(),
            $this->transactionalService
        );
    }

    private function mockSetUp(): void
    {
        $this->mockCoinsRepository = $this->createMock(CoinsRepository::class);
        $this->mockMachineStatusRepository = $this->createMock(MachineStatusRepository::class);
        $this->mockTransactionalService = $this->createMock(TransactionalService::class);

        $this->mockUseCase = new CoinsGetBackUseCase(
            $this->mockCoinsRepository,
            $this->mockMachineStatusRepository,
            new CoinChangeCalculator(),
            $this->mockTransactionalService
        );
    }

    private function givenMachineStatusWithBalance(float $balance): void
    {
        $this->machineStatusRepository->save(
            MachineStatus::create(
                MachineStatusId::generate(),
                MachineStatusBalance::create($balance)
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

    public function testItReturnsTheCoinsForTheGivenBalance(): void
    {
        $this->givenMachineStatusWithBalance(1.00);
        $this->givenCoin(1.00, 1);

        $dto = $this->useCase->__invoke();

        self::assertSame(['1.00' => 1], $dto->coins);
    }

    public function testItResolvesChangeAcrossDenominations(): void
    {
        $this->givenMachineStatusWithBalance(0.30);
        $quarter = $this->givenCoin(0.25, 1);
        $nickel = $this->givenCoin(0.05, 2);

        $dto = $this->useCase->__invoke();

        self::assertSame(['0.25' => 1, '0.05' => 1], $dto->coins);
        self::assertSame(0, $quarter->quantity()->value());
        self::assertSame(1, $nickel->quantity()->value());
    }

    public function testItDeductsTheExactQuantityNotResettingTheType(): void
    {
        $this->givenMachineStatusWithBalance(0.10);
        $dime = $this->givenCoin(0.10, 5);

        $dto = $this->useCase->__invoke();

        self::assertSame(['0.10' => 1], $dto->coins);
        self::assertSame(4, $dime->quantity()->value());
    }

    public function testItResolvesTheGreedyCounterexampleWithDimes(): void
    {
        $this->givenMachineStatusWithBalance(0.30);
        $quarter = $this->givenCoin(0.25, 1);
        $this->givenCoin(0.10, 3);

        $dto = $this->useCase->__invoke();

        self::assertSame(['0.10' => 3], $dto->coins);
        self::assertSame(1, $quarter->quantity()->value());
    }

    public function testItResetsTheMachineStatusBalance(): void
    {
        $this->givenMachineStatusWithBalance(0.10);
        $this->givenCoin(0.10, 1);

        $this->useCase->__invoke();

        self::assertTrue($this->machineStatusRepository->find()->balance()->isEmpty());
    }

    public function testItThrowsWhenThereIsNoMachineStatus(): void
    {
        try {
            $this->useCase->__invoke();
            self::fail('Expected MachineStatusBalanceIsEmpty to be thrown.');
        } catch (MachineStatusBalanceIsEmpty) {
        }

        self::assertSame([], $this->coinsRepository->findAll());
    }

    public function testItThrowsWhenTheBalanceIsZero(): void
    {
        $this->givenMachineStatusWithBalance(0.0);

        $this->expectException(MachineStatusBalanceIsEmpty::class);

        $this->useCase->__invoke();
    }

    public function testItThrowsWhenThereAreNotEnoughCoins(): void
    {
        $this->givenMachineStatusWithBalance(1.00);
        $this->givenCoin(0.10, 5);

        $this->expectException(NotEnoughCoinsToReturn::class);

        $this->useCase->__invoke();
    }

    public function testItWrapsCoinAndStatusSavesInATransaction(): void
    {
        $this->mockSetUp();

        $this->mockTransactionalService->expects($this->once())
            ->method('execute')
            ->willReturnCallback(static fn(callable $operation): mixed => $operation());

        $coin = Coin::create(
            CoinId::generate(),
            CoinValue::create(0.10),
            CoinQuantity::create(3)
        );
        $machineStatus = MachineStatus::create(
            MachineStatusId::generate(),
            MachineStatusBalance::create(0.10)
        );

        $this->mockCoinsRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$coin]);
        $this->mockCoinsRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn(Coin $saved): bool => $saved->quantity()->value() === 2
            ));
        $this->mockMachineStatusRepository->expects($this->once())
            ->method('find')
            ->willReturn($machineStatus);
        $this->mockMachineStatusRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn(MachineStatus $saved): bool => $saved->balance()->isEmpty()
            ));

        $dto = $this->mockUseCase->__invoke();

        self::assertSame(['0.10' => 1], $dto->coins);
        self::assertSame(2, $coin->quantity()->value());
    }
}
