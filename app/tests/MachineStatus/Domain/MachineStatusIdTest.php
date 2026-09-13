<?php

namespace App\Tests\MachineStatus\Domain;

use App\Coin\Domain\CoinId;
use PHPUnit\Framework\TestCase;

final class MachineStatusIdTest extends TestCase
{
    public function testItGeneratesAValidV4UuidMachineStatusId(): void
    {
        $id = CoinId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id->value()
        );
    }
}
