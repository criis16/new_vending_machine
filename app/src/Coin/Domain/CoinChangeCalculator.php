<?php

namespace App\Coin\Domain;

use App\Coin\Domain\Exceptions\NotEnoughCoinsToReturn;

final class CoinChangeCalculator
{
    /**
     * @param Coin[] $coins
     */
    public function calculate(float $balance, array $coins): CoinChange
    {
        $target = (int)round($balance * 100);
        $denominations = $this->denominationsFrom($coins);

        $units = $this->findCombination($target, $denominations);

        if (null === $units) {
            throw new NotEnoughCoinsToReturn();
        }

        return CoinChange::fromUnits($units);
    }

    /**
     * @param Coin[] $coins
     *
     * @return array<int, array{type: CoinType, cents: int, available: int}>
     */
    private function denominationsFrom(array $coins): array
    {
        $availableByType = $this->groupByType($coins);

        $denominations = [];
        foreach ($this->typesDescending() as $type) {
            $denominations[] = [
                'type' => $type,
                'cents' => (int)round(((float)$type->value) * 100),
                'available' => $availableByType[$type->value] ?? 0,
            ];
        }

        return $denominations;
    }


    /**
     * @param Coin[] $coins
     *
     * @return array<string, int>
     */
    private function groupByType(array $coins): array
    {
        $available = [];
        foreach ($coins as $coin) {
            $type = CoinType::fromValue($coin->value()->value());
            $available[$type->value] = ($available[$type->value] ?? 0) + $coin->quantity()->value();
        }

        return $available;
    }

    /**
     * @return CoinType[]
     */
    private function typesDescending(): array
    {
        return [
            CoinType::OneEuro,
            CoinType::TwentyFiveCents,
            CoinType::TenCents,
            CoinType::FiveCents,
        ];
    }

    /**
     * @param array<int, array{type: CoinType, cents: int, available: int}> $denominations
     *
     * @return array<string, int>|null keyed by CoinType value, null if impossible
     */
    private function findCombination(int $target, array $denominations): ?array
    {
        $solutions = [0 => []];

        foreach ($denominations as $denomination) {
            $typeValue = $denomination['type']->value;
            $cents = $denomination['cents'];
            $stock = $denomination['available'];

            $extended = $solutions;

            foreach ($solutions as $amount => $units) {
                $maxTake = min($stock, intdiv($target - $amount, $cents));

                for ($take = 1; $take <= $maxTake; ++$take) {
                    $newAmount = $amount + $take * $cents;

                    if (isset($extended[$newAmount])) {
                        continue;
                    }

                    $extended[$newAmount] = $units + [$typeValue => $take];
                }
            }

            $solutions = $extended;
        }

        return $solutions[$target] ?? null;
    }
}
