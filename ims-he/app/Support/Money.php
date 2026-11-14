<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);
        $negative = $value < 0;

        return ($negative ? '-' : '') . '₱' . number_format(abs($value), 2, '.', ',');
    }

    public static function parse(?string $input): ?string
    {
        $value = trim((string) $input);

        if ($value === '') {
            return null;
        }

        $value = str_replace(['₱', ',', ' '], '', $value);

        if (! preg_match('/^-?(?:\d+(?:\.\d+)?|\.\d+)$/', $value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
