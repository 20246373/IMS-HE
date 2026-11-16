<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_format_returns_peso_amounts_with_two_decimals(): void
    {
        $this->assertSame('₱1,234.50', Money::format(1234.5));
        $this->assertSame('₱0.00', Money::format(0));
        $this->assertSame('₱0.00', Money::format(null));
        $this->assertSame('-₱1,234.50', Money::format(-1234.5));
        $this->assertSame('₱1,234.50', money('1234.5'));
    }

    public function test_parse_removes_currency_formatting_and_handles_empty_input(): void
    {
        $this->assertSame('1234.50', Money::parse('1,234.50'));
        $this->assertSame('1234.50', Money::parse('₱1,234.50'));
        $this->assertSame('-1234.50', Money::parse('-₱1,234.50'));
        $this->assertNull(Money::parse(null));
        $this->assertNull(Money::parse(''));
        $this->assertNull(Money::parse('not money'));
    }
}
