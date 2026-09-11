<?php

namespace App\Tests\Shared\Domain\ValueObject\DataProvider;

final class StringTestDataProvider
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function anyStrings(): iterable
    {
        yield 'plain text' => ['value'];
        yield 'dotted path' => ['value.value'];
        yield 'empty string' => [''];
        yield 'unicode' => ['café'];
    }
}
