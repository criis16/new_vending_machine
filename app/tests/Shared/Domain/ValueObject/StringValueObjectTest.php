<?php

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;
use App\Tests\Shared\Domain\ValueObject\DataProvider\StringTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class StringValueObjectStub extends StringValueObject
{
}

final class StringValueObjectTest extends TestCase
{
    #[DataProviderExternal(StringTestDataProvider::class, 'anyStrings')]
    public function testItStoresAnyStringValue(string $value): void
    {
        self::assertSame($value, new StringValueObjectStub($value)->value());
    }
}
