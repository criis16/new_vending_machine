<?php

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\IntQuantityValueObject;
use App\Tests\Shared\Domain\ValueObject\DataProvider\IntQuantityTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class IntQuantityValueObjectStub extends IntQuantityValueObject
{
}

final class IntQuantityValueObjectTest extends TestCase
{
    #[DataProviderExternal(IntQuantityTestDataProvider::class, 'anyIntegers')]
    public function testItStoresAnyInt(int $value): void
    {
        self::assertSame($value, new IntQuantityValueObjectStub($value)->value());
    }

    #[DataProviderExternal(IntQuantityTestDataProvider::class, 'nonNegativeValues')]
    public function testIsNegativeValueReturnsFalseForNonNegativeValues(int $value): void
    {
        $stub = new IntQuantityValueObjectStub($value);
        self::assertFalse($stub->isNegativeValue());
    }

    #[DataProviderExternal(IntQuantityTestDataProvider::class, 'negativeValues')]
    public function testIsNegativeValueReturnsTrueForNegativeValues(int $value): void
    {
        $stub = new IntQuantityValueObjectStub($value);
        self::assertTrue($stub->isNegativeValue());
    }

}
