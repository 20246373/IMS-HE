<?php

namespace App\Support;

class Money
{
    /** Philippine peso display used everywhere: 1234.5 becomes "₱1,234.50". */
    public static function format(float|int|string|null $amount): string
    {
        $value = (float) ($amount ?? 0);
        $negative = $value < 0;

        return ($negative ? '-' : '') . '₱' . number_format(abs($value), 2, '.', ',');
    }

    /** Same as format(); used by the @peso Blade directive. */
    public static function peso(float|int|string|null $amount): string
    {
        return self::format($amount);
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
