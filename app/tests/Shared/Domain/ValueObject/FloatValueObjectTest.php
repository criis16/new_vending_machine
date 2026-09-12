<?php

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\FloatValueObject;
use App\Tests\Shared\Domain\ValueObject\DataProvider\FloatTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class FloatValueObjectStub extends FloatValueObject
{
}

final class FloatValueObjectTest extends TestCase
{
    #[DataProviderExternal(FloatTestDataProvider::class, 'anyFloats')]
    public function testItStoresAnyFloat(float $value): void
    {
        self::assertSame($value, new FloatValueObjectStub($value)->value());
    }

    #[DataProviderExternal(FloatTestDataProvider::class, 'nonNegativeValues')]
    public function testIsNegativeValueReturnsFalseForNonNegativeValues(float $value): void
    {
        $stub = new FloatValueObjectStub($value);
        self::assertFalse($stub->isNegativeValue());
    }

    #[DataProviderExternal(FloatTestDataProvider::class, 'negativeValues')]
    public function testIsNegativeValueReturnsTrueForNegativeValues(float $value): void
    {
        $stub = new FloatValueObjectStub($value);
        self::assertTrue($stub->isNegativeValue());
    }
}
