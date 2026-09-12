<?php

namespace App\Shared\Domain\Criteria;

final readonly class Filters implements \IteratorAggregate
{
    private function __construct(
        private array $filters,
    )
    {
        foreach ($this->filters as $filter) {
            if (!$filter instanceof Filter) {
                throw new \InvalidArgumentException(sprintf(
                    '<%s> only accepts instances of <%s>.',
                    self::class,
                    Filter::class
                ));
            }
        }
    }

    public static function fromValues(array $values): self
    {
        return new self(
            array_map(
                static fn(array $values): Filter => Filter::fromValues($values),
                $values
            )
        );
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function add(Filter $filter): self
    {
        return new self([...$this->filters, $filter]);
    }

    public function filters(): array
    {
        return $this->filters;
    }

    public function count(): int
    {
        return count($this->filters);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->filters);
    }
}
