<?php

namespace App\Support;

class MoneyInput
{
    public static function digitsOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_replace('/\D/', '', (string) $value);
    }
}
