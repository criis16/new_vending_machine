<?php

namespace App\Tests\Coin\Application;

use App\Coin\Application\CoinInsertUseCase;
use App\Coin\Application\CoinsInsertDTO;
use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinsRepository;
use App\Coin\Domain\CoinValue;
use App\Coin\Domain\Exceptions\CoinNotValid;
use App\MachineStatus\Domain\MachineStatus;
use App\MachineStatus\Domain\MachineStatusBalance;
use App\MachineStatus\Domain\MachineStatusId;
use App\MachineStatus\Domain\MachineStatusRepository;
use App\Shared\Application\TransactionalService;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use App\Tests\MachineStatus\Application\InMemoryMachineStatusRepository;
use App\Tests\Shared\Application\InMemoryTransactionalService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CoinInsertUseCaseTest extends TestCase
{
    private CoinInsertUseCase $useCase;
    private CoinsRepository $repository;
    private MachineStatusRepository $machineStatusRepository;
    private TransactionalService $transactionalService;

    private CoinsRepository|MockObject $mockRepository;
    private MachineStatusRepository|MockObject $mockMachineStatusRepository;
    private TransactionalService|MockObject $mockTransactionalService;
    private CoinInsertUseCase $mockUseCase;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCoinsRepository();
        $this->machineStatusRepository = new InMemoryMachineStatusRepository();
        $this->transactionalService = new InMemoryTransactionalService();
        $this->useCase = new CoinInsertUseCase(
            $this->repository,
            $this->machineStatusRepository,
            $this->transactionalService
        );
    }

    private function mockSetUp(): void
    {
        $this->mockRepository = $this->createMock(CoinsRepository::class);
        $this->mockMachineStatusRepository = $this->createMock(MachineStatusRepository::class);
        $this->mockTransactionalService = $this->createMock(TransactionalService::class);
        $this->mockUseCase = new CoinInsertUseCase(
            $this->mockRepository,
            $this->mockMachineStatusRepository,
            $this->mockTransactionalService
        );
    }

    private function anyCriteria(): Criteria
    {
        return new Criteria(Filters::none(), Order::none());
    }

    public function testItCreatesAndSavesANewCoinWhenNoneExists(): void
    {
        $this->useCase->__invoke(new CoinsInsertDTO(0.10));

        $coins = $this->repository->searchByCriteria($this->anyCriteria());
        self::assertCount(1, $coins);
        self::assertInstanceOf(Coin::class, $coins[0]);
        self::assertSame(0.10, $coins[0]->value()->value());
        self::assertSame(1, $coins[0]->quantity()->value());

        self::assertSame(0.10, $this->machineStatusRepository->find()->balance()->value());
    }

    public function testItIncrementsQuantityWhenCoinAlreadyExists(): void
    {
        $this->repository->save(Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize()));

        $this->useCase->__invoke(new CoinsInsertDTO(0.10));

        $coins = $this->repository->searchByCriteria($this->anyCriteria());
        self::assertCount(1, $coins);
        self::assertSame(0.10, $coins[0]->value()->value());
        self::assertSame(2, $coins[0]->quantity()->value());

        self::assertSame(0.10, $this->machineStatusRepository->find()->balance()->value());
    }

    public function testItPicksFirstMatchWhenSeveralCoinsShareTheValue(): void
    {
        $this->repository->save(
            Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize())
        );
        $this->repository->save(
            Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize())
        );

        $this->useCase->__invoke(new CoinsInsertDTO(0.10));

        $coins = $this->repository->searchByCriteria($this->anyCriteria());
        self::assertCount(2, $coins);
        self::assertSame(2, $coins[0]->quantity()->value());
        self::assertSame(1, $coins[1]->quantity()->value());

        self::assertSame(0.10, $this->machineStatusRepository->find()->balance()->value());
    }

    public function testItAccumulatesTheBalanceAcrossInserts(): void
    {
        $this->useCase->__invoke(new CoinsInsertDTO(0.10));
        $this->useCase->__invoke(new CoinsInsertDTO(0.10));

        $coins = $this->repository->searchByCriteria($this->anyCriteria());
        self::assertCount(1, $coins);
        self::assertSame(2, $coins[0]->quantity()->value());

        self::assertSame(0.20, $this->machineStatusRepository->find()->balance()->value());
    }

    public function testItReusesTheExistingMachineStatusAndCreditsIt(): void
    {
        $this->machineStatusRepository->save(
            MachineStatus::create(MachineStatusId::generate(), MachineStatusBalance::create(0.05))
        );

        $this->useCase->__invoke(new CoinsInsertDTO(0.10));

        self::assertSame(0.15, $this->machineStatusRepository->find()->balance()->value());
    }

    public function testItThrowsCoinNotValidWithoutSaving(): void
    {
        try {
            $this->useCase->__invoke(new CoinsInsertDTO(0.20));
            self::fail('Expected CoinNotValid to be thrown.');
        } catch (CoinNotValid) {
        }

        self::assertCount(0, $this->repository->searchByCriteria($this->anyCriteria()));
        self::assertNull($this->machineStatusRepository->find());
    }

    public function testItSavesTheExactFoundCoinInstanceOnIncrement(): void
    {
        $this->mockSetUp();

        $this->mockTransactionalService->expects($this->once())
            ->method('execute')
            ->willReturnCallback(static fn(callable $operation): mixed => $operation());

        $existing = Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize());

        $this->mockRepository->expects($this->once())
            ->method('searchByCriteria')
            ->willReturn([$existing]);
        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($existing);
        $this->mockMachineStatusRepository->expects($this->once())
            ->method('find');
        $this->mockMachineStatusRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn(MachineStatus $machineStatus): bool => 0.10 === $machineStatus->balance()->value()
            ));

        $this->mockUseCase->__invoke(new CoinsInsertDTO(0.10));

        self::assertSame(2, $existing->quantity()->value());
    }

    public function testItSavesANewCoinMatchingValueAndQuantity(): void
    {
        $this->mockSetUp();

        $this->mockTransactionalService->expects($this->once())
            ->method('execute')
            ->willReturnCallback(static fn(callable $operation): mixed => $operation());

        $this->mockRepository->expects($this->once())
            ->method('searchByCriteria')
            ->willReturn([]);
        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn(Coin $coin): bool => $coin->value()->value() === 0.10
                    && $coin->quantity()->value() === 1
                    && $coin->id() instanceof CoinId
            ));
        $this->mockMachineStatusRepository->expects($this->once())
            ->method('find');
        $this->mockMachineStatusRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn(MachineStatus $machineStatus): bool => 0.10 === $machineStatus->balance()->value()
            ));

        $this->mockUseCase->__invoke(new CoinsInsertDTO(0.10));
    }

    public function testItNeverTouchesTheRepositoriesOrTransactionForAnInvalidCoin(): void
    {
        $this->mockSetUp();

        $this->mockTransactionalService->expects($this->never())
            ->method('execute');

        $this->expectException(CoinNotValid::class);
        $this->mockRepository->expects($this->never())->method('searchByCriteria');
        $this->mockRepository->expects($this->never())->method('save');
        $this->mockMachineStatusRepository->expects($this->never())->method('find');
        $this->mockMachineStatusRepository->expects($this->never())->method('save');
        $this->mockTransactionalService->expects($this->never())->method('execute');

        $this->mockUseCase->__invoke(new CoinsInsertDTO(0.20));
    }
}
