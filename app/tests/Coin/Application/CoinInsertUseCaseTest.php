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
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filters;
use App\Shared\Domain\Criteria\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CoinInsertUseCaseTest extends TestCase
{
    private CoinInsertUseCase $useCase;
    private CoinsRepository $repository;
    private CoinsRepository|MockObject $mockRepository;
    private CoinInsertUseCase $mockUseCase;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCoinsRepository();
        $this->useCase = new CoinInsertUseCase($this->repository);
    }

    private function mockSetUp(): void
    {
        $this->mockRepository = $this->createMock(CoinsRepository::class);
        $this->mockUseCase = new CoinInsertUseCase($this->mockRepository);
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
    }

    public function testItIncrementsQuantityWhenCoinAlreadyExists(): void
    {
        $this->repository->save(Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize()));

        $this->useCase->__invoke(new CoinsInsertDTO(0.10));

        $coins = $this->repository->searchByCriteria($this->anyCriteria());
        self::assertCount(1, $coins);
        self::assertSame(0.10, $coins[0]->value()->value());
        self::assertSame(2, $coins[0]->quantity()->value());
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
    }

    public function testItThrowsCoinNotValidWithoutSaving(): void
    {
        try {
            $this->useCase->__invoke(new CoinsInsertDTO(0.20));
            self::fail('Expected CoinNotValid to be thrown.');
        } catch (CoinNotValid) {
        }

        $coins = $this->repository->searchByCriteria($this->anyCriteria());
        self::assertCount(0, $coins);
    }

    public function testItSavesTheExactFoundCoinInstanceOnIncrement(): void
    {
        $this->mockSetUp();

        $existing = Coin::create(CoinId::generate(), CoinValue::create(0.10), CoinQuantity::initialize());

        $this->mockRepository->expects($this->once())
            ->method('searchByCriteria')
            ->willReturn([$existing]);
        $this->mockRepository->expects($this->once())
            ->method('save')
            ->with($existing);

        $this->mockUseCase->__invoke(new CoinsInsertDTO(0.10));

        self::assertSame(2, $existing->quantity()->value());
    }

    public function testItSavesANewCoinMatchingValueAndQuantity(): void
    {
        $this->mockSetUp();

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

        $this->mockUseCase->__invoke(new CoinsInsertDTO(0.10));
    }

    public function testItNeverTouchesTheRepositoryForAnInvalidCoin(): void
    {
        $this->mockSetUp();

        $this->expectException(CoinNotValid::class);
        $this->mockRepository->expects($this->never())->method('searchByCriteria');
        $this->mockRepository->expects($this->never())->method('save');

        $this->mockUseCase->__invoke(new CoinsInsertDTO(0.20));
    }

}
