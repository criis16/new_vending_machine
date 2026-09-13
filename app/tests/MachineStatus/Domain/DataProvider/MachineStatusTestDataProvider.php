<?php

namespace App\Tests\MachineStatus\Domain\DataProvider;

final class MachineStatusTestDataProvider
{
    /** @return iterable<string, array{float}> */
    public static function validBalances(): iterable
    {
        yield 'zero' => [0.0];
        yield 'five cents' => [0.05];
        yield 'one euro' => [1.0];
    }

    /** @return iterable<string, array{float}> */
    public static function negativeBalances(): iterable
    {
        yield 'negative five cents' => [-0.05];
        yield 'negative one euro' => [-1.0];
    }

}
