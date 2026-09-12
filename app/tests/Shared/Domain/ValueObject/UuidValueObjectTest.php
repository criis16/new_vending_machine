<?php

namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\UuidValueObject;
use App\Tests\Shared\Domain\ValueObject\DataProvider\UuidTestDataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UuidValueObjectStub extends UuidValueObject
{
}

final class UuidValueObjectTest extends TestCase
{
    public function testItKeepsAWellFormedUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        self::assertSame($uuid, new UuidValueObjectStub($uuid)->value());
    }

    #[DataProviderExternal(UuidTestDataProvider::class, 'invalidUuids')]
    public function testItRejectsAnInvalidUuid(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UuidValueObjectStub($value);
    }

    public function testItGeneratesAValidUuid(): void
    {
        $id = UuidValueObjectStub::generate();

        self::assertInstanceOf(UuidValueObjectStub::class, $id);
        self::assertTrue(Uuid::isValid($id->value()));
    }

    public function testItGeneratesDistinctUuids(): void
    {
        self::assertNotSame(
            UuidValueObjectStub::generate()->value(),
            UuidValueObjectStub::generate()->value()
        );
    }

    #[DataProviderExternal(UuidTestDataProvider::class, 'validUuids')]
    public function testItComparesEqualityByValue(string $uuid): void
    {
        $stub = new UuidValueObjectStub($uuid);

        self::assertTrue($stub->equals($stub));
        self::assertFalse($stub->equals(UuidValueObjectStub::generate()));
    }

    #[DataProviderExternal(UuidTestDataProvider::class, 'validUuids')]
    public function testItStringifiesToItsValue(string $uuid): void
    {
        self::assertSame($uuid, (string)new UuidValueObjectStub($uuid));
    }
}
