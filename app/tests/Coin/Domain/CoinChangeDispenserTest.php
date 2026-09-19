<?php

namespace App\Tests\Coin\Domain;

use App\Coin\Domain\Coin;
use App\Coin\Domain\CoinChange;
use App\Coin\Domain\CoinChangeDispenser;
use App\Coin\Domain\CoinId;
use App\Coin\Domain\CoinQuantity;
use App\Coin\Domain\CoinValue;
use PHPUnit\Framework\TestCase;

final class CoinChangeDispenserTest extends TestCase
{
    private CoinChangeDispenser $coinChangeDispenser;

    public function setUp(): void
    {
        $this->coinChangeDispenser = new CoinChangeDispenser();
    }

    private function givenCoin(float $value, int $quantity): Coin
    {
        return Coin::create(
            CoinId::generate(),
            CoinValue::create($value),
            CoinQuantity::create($quantity)
        );
    }

    public function testItTakesTheRequestedUnitsFromAMatchingType(): void
    {
        $quarter = $this->givenCoin(0.25, 1);

        $dispensed = $this->coinChangeDispenser->dispense(
            CoinChange::fromUnits(['0.25' => 1]),
            [$quarter]
        );

        self::assertSame([$quarter], $dispensed);
        self::assertSame(0, $quarter->quantity()->value());
    }

    public function testItOnlyTakesTheUnitsNeeded(): void
    {
        $dime = $this->givenCoin(0.10, 5);

        $dispensed = $this->coinChangeDispenser->dispense(
            CoinChange::fromUnits(['0.10' => 2]),
            [$dime]
        );

        self::assertSame([$dime], $dispensed);
        self::assertSame(3, $dime->quantity()->value());
    }

    public function testItSpreadsTheUnitsAcrossCoinsOfTheSameType(): void
    {
        $firstDime = $this->givenCoin(0.10, 2);
        $secondDime = $this->givenCoin(0.10, 3);

        $dispensed = $this->coinChangeDispenser->dispense(
            CoinChange::fromUnits(['0.10' => 3]),
            [$firstDime, $secondDime]
        );

        self::assertSame([$firstDime, $secondDime], $dispensed);
        self::assertSame(0, $firstDime->quantity()->value());
        self::assertSame(2, $secondDime->quantity()->value());
    }

    public function testItDoesNotTouchCoinsOfUnrelatedTypes(): void
    {
        $quarter = $this->givenCoin(0.25, 1);
        $dime = $this->givenCoin(0.10, 1);

        $dispensed = $this->coinChangeDispenser->dispense(
            CoinChange::fromUnits(['0.25' => 1]),
            [$quarter, $dime]
        );

        self::assertSame([$quarter], $dispensed);
        self::assertSame(1, $dime->quantity()->value());
    }

    public function testItSkipsCoinsWithoutStock(): void
    {
        $emptyDime = $this->givenCoin(0.10, 0);
        $dime = $this->givenCoin(0.10, 1);

        $dispensed = $this->coinChangeDispenser->dispense(
            CoinChange::fromUnits(['0.10' => 1]),
            [$emptyDime, $dime]
        );

        self::assertSame([$dime], $dispensed);
        self::assertSame(0, $emptyDime->quantity()->value());
        self::assertSame(0, $dime->quantity()->value());
    }

    public function testItReturnsNothingForAnEmptyChange(): void
    {
        $dime = $this->givenCoin(0.10, 1);

        $dispensed = $this->coinChangeDispenser->dispense(CoinChange::empty(), [$dime]);

        self::assertSame([], $dispensed);
        self::assertSame(1, $dime->quantity()->value());
    }
}
