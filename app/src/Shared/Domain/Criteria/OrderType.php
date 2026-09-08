<?php

namespace App\Shared\Domain\Criteria;

enum OrderType: string
{
    case ASC = 'asc';
    case DESC = 'desc';
    case NONE = 'none';

    public function isNone(): bool
    {
        return $this->value === self::NONE->value;
    }
}
