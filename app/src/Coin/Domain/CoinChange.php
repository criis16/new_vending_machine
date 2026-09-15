<?php

namespace App\Coin\Domain;

use Traversable;

final readonly class CoinChange implements \IteratorAggregate
{
    /** @param array<string, int> $units keyed by CoinType value */
    private function __construct(private array $units)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromUnits(array $units): self
    {
        return new self($units);
    }

    public function unitsFor(CoinType $type): int
    {
        return $this->units[$type->value] ?? 0;
    }

    public function totalInCents(): int
    {
        $total = 0;
        foreach ($this->units as $value => $units) {
            $total += (int)round(((float)$value) * 100) * $units;
        }

        return $total;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->units);
    }
}
