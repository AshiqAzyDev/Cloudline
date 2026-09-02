<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_gbp_converts_to_minor_units(): void
    {
        $this->assertSame(12345, Money::toMinor('123.45', 'GBP'));
        $this->assertSame('123.45', Money::fromMinor(12345, 'GBP'));
        $this->assertSame('£123.45', Money::format(12345, 'GBP'));
    }

    public function test_inr_uses_two_decimals(): void
    {
        $this->assertSame(15000000, Money::toMinor('150000.00', 'INR'));
        $this->assertSame('₹150,000.00', Money::format(15000000, 'INR'));
    }

    public function test_jpy_is_zero_decimal(): void
    {
        $this->assertSame(0, Money::decimals('JPY'));
        $this->assertSame(1500, Money::toMinor('1500', 'JPY'));
        $this->assertSame('¥1,500', Money::format(1500, 'JPY'));
    }

    public function test_unknown_currency_falls_back_without_error(): void
    {
        $this->assertSame(2, Money::decimals('XYZ'));
        $this->assertSame('XYZ ', Money::symbol('XYZ'));
        $this->assertSame('XYZ 10.00', Money::format(1000, 'XYZ'));
    }
}
