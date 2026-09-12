<?php

namespace App\Tests\Shared\Domain\Criteria;

use App\Shared\Domain\Criteria\Filter;
use App\Shared\Domain\Criteria\FilterField;
use App\Shared\Domain\Criteria\FilterOperator;
use App\Shared\Domain\Criteria\FilterValue;
use PHPUnit\Framework\TestCase;

final class FilterTest extends TestCase
{
    public function testItBuildsFromValues(): void
    {
        $filter = Filter::fromValues([
            'field' => 'value',
            'operator' => '=',
            'value' => '0.10',
        ]);

        self::assertSame('value', $filter->field()->value());
        self::assertSame(FilterOperator::EQUAL, $filter->operator());
        self::assertSame('0.10', $filter->value()->value());
    }

    public function testItHoldsItsParts(): void
    {
        $filter = new Filter(
            new FilterField('quantity'),
            FilterOperator::GT,
            new FilterValue('2')
        );

        self::assertSame('quantity', $filter->field()->value());
        self::assertSame(FilterOperator::GT, $filter->operator());
        self::assertSame('2', $filter->value()->value());
    }

    public function testItSerializesFieldOperatorAndValue(): void
    {
        $filter = Filter::fromValues([
            'field' => 'value',
            'operator' => '=',
            'value' => '0.10',
        ]);

        self::assertSame('value.=.0.10', $filter->serialize());
    }

    public function testItRejectsAnUnknownOperator(): void
    {
        $this->expectException(\ValueError::class);

        Filter::fromValues([
            'field' => 'value',
            'operator' => '=??',
            'value' => '0.10',
        ]);
    }
}
