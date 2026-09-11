<?php

namespace App\Tests\Shared\Domain\ValueObject\DataProvider;

final class UuidTestDataProvider
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidUuids(): iterable
    {
        yield 'plain text' => ['not-a-uuid'];
        yield 'too short hex' => ['550e8400e29b41d4a716446655440000'];
        yield 'empty' => [''];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validUuids(): iterable
    {
        yield 'v1' => ['00000000-0000-1000-8000-000000000000'];
        yield 'v3' => ['00000000-0000-3000-8000-000000000000'];
        yield 'v4' => ['550e8400-e29b-41d4-a716-446655440000'];
        yield 'v5' => ['00000000-0000-5000-8000-000000000000'];
        yield 'v6' => ['1ef30080-9c3c-6000-8000-000000000000'];
        yield 'v7' => ['017f22e2-79b0-7cc3-98c4-dc0c0c07398f'];
        yield 'v8' => ['00000000-0000-8000-8000-000000000000'];
        yield 'nil' => ['00000000-0000-0000-0000-000000000000'];
        yield 'max' => ['ffffffff-ffff-ffff-ffff-ffffffffffff'];
        yield 'uppercase' => ['550E8400-E29B-41D4-A716-446655440000'];
    }

}
