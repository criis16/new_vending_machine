<?php

namespace App\Shared\Infrastructure\Persistence\Doctrine;

interface DoctrineCustomType
{
    public static function customTypeName(): string;
}
