<?php

namespace App\Tests\Shared\Domain\Criteria;

use App\Shared\Domain\Criteria\Filter;
use App\Shared\Domain\Criteria\Filters;
use PHPUnit\Framework\TestCase;

final class FiltersTest extends TestCase
{
    public function testItBuildsFiltersFromValues(): void
    {
        $filters = Filters::fromValues([
            ['field' => 'value', 'operator' => '=', 'value' => '0.10'],
            ['field' => 'quantity', 'operator' => '>', 'value' => '1'],
        ]);

        self::assertCount(2, $filters);
        self::assertCount(2, $filters->filters());
        self::assertFalse($filters->isEmpty());
        self::assertContainsOnlyInstancesOf(Filter::class, $filters->filters());
    }

    public function testItCreatesEmptyFilters(): void
    {
        $filters = Filters::none();

        self::assertCount(0, $filters);
        self::assertTrue($filters->isEmpty());
        self::assertSame([], $filters->filters());
    }

    public function testAddReturnsANewInstanceWithoutMutatingTheOriginal(): void
    {
        $original = Filters::none();
        $filter = Filter::fromValues(['field' => 'value', 'operator' => '=', 'value' => '0.10']);

        $added = $original->add($filter);

        self::assertNotSame($original, $added);
        self::assertTrue($original->isEmpty());
        self::assertCount(1, $added);
        self::assertSame([$filter], $added->filters());
    }

    public function testItIteratesOverFilters(): void
    {
        $filters = Filters::fromValues([
            ['field' => 'value', 'operator' => '=', 'value' => '0.25'],
        ]);

        $iterated = iterator_to_array($filters);

        self::assertCount(1, $iterated);
        self::assertInstanceOf(Filter::class, reset($iterated));
    }
}
