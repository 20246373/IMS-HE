<?php

namespace App\Support;

class Money
{
    /** Philippine peso display used everywhere: 1234.5 becomes "₱1,234.50". */
    public static function peso(int|float|string|null $amount): string
    {
        return '₱' . number_format((float) $amount, 2);
    }
}
