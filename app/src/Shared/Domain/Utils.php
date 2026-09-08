<?php

namespace App\Shared\Domain;

final class Utils
{
    public static function toSnakeCase(string $text): string
    {
        return ctype_lower($text) ? $text : strtolower((string)preg_replace('/([^A-Z\s])([A-Z])/', '$1_$2', $text));
    }
}
