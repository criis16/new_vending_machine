<?php

namespace App\Tests\Shared\Domain;

use App\Shared\Domain\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
    /** @return iterable<string, array{string, string}> */
    public static function snakeCaseExamples(): iterable
    {
        yield 'pascal case' => ['CoinId', 'coin_id'];
        yield 'multi word pascal' => ['DoctrineCriteriaConverter', 'doctrine_criteria_converter'];
        yield 'already lowercase' => ['hello', 'hello'];
        yield 'single word uppercase' => ['ABC', 'abc'];
        yield 'acronym in middle' => ['someHTTPString', 'some_httpstring'];
        yield 'common field name' => ['orderId', 'order_id'];
    }

    #[DataProvider('snakeCaseExamples')]
    public function testItConvertsToSnakeCase(string $input, string $expected): void
    {
        self::assertSame($expected, Utils::toSnakeCase($input));
    }
}
