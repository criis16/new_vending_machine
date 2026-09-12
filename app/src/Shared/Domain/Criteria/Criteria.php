<?php

namespace App\Shared\Domain\Criteria;

final readonly class Criteria
{
    public function __construct(
        private Filters $filters,
        private Order   $order,
        private ?int    $offset = null,
        private ?int    $limit = null,
    )
    {
    }

    public function hasFilters(): bool
    {
        return !$this->filters->isEmpty();
    }

    public function hasOrder(): bool
    {
        return !$this->order->isNone();
    }

    public function plainFilters(): array
    {
        return $this->filters->filters();
    }

    public function filters(): Filters
    {
        return $this->filters;
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function offset(): ?int
    {
        return $this->offset;
    }

    public function limit(): ?int
    {
        return $this->limit;
    }
}
