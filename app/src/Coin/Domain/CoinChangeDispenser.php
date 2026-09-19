<?php

namespace App\Coin\Domain;

final class CoinChangeDispenser
{
    /**
     * @param Coin[] $coins
     *
     * @return Coin[] Solo las monedas modificadas, para que el use case las persista
     */
    public function dispense(CoinChange $change, array $coins): array
    {
        $coinsByType = $this->groupCoinsByType($coins);
        $dispensed = [];

        foreach ($change as $typeValue => $unitsNeeded) {
            foreach ($coinsByType[$typeValue] ?? [] as $coin) {
                if ($unitsNeeded <= 0) {
                    break;
                }

                $quantity = $coin->quantity()->value();
                if ($quantity === 0) {
                    continue;
                }

                $unitsToTake = min($unitsNeeded, $quantity);
                $coin->decreaseQuantity(CoinQuantity::create($unitsToTake));
                $dispensed[] = $coin;
                $unitsNeeded -= $unitsToTake;
            }
        }

        return $dispensed;
    }

    /**
     * @param Coin[] $coins
     *
     * @return array<string, Coin[]>
     */
    private function groupCoinsByType(array $coins): array
    {
        $grouped = [];
        foreach ($coins as $coin) {
            $grouped[CoinType::fromValue($coin->value()->value())->value][] = $coin;
        }

        return $grouped;
    }
}
