<?php

namespace Tests\Unit;

use App\Support\FinancialYear;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class FinancialYearTest extends TestCase
{
    public function test_april_to_march_boundaries(): void
    {
        $before = CarbonImmutable::parse('2026-03-31');
        $after = CarbonImmutable::parse('2026-04-01');

        $this->assertSame(2025, FinancialYear::startFor($before)->year);
        $this->assertSame(2026, FinancialYear::startFor($after)->year);
        $this->assertSame('2027-03-31', FinancialYear::endFor($after)->toDateString());
        $this->assertSame('FY 2026/27', FinancialYear::label(2026));
    }

    public function test_available_start_years_follow_current_and_invoice_bounds(): void
    {
        $now = CarbonImmutable::parse('2026-08-15');

        $this->assertSame([2026], FinancialYear::availableStartYears($now));
        $this->assertSame([2026, 2025, 2024], FinancialYear::availableStartYears($now, 2024, 2025));
        $this->assertSame([2027, 2026], FinancialYear::availableStartYears($now, null, 2027));
        $this->assertSame([2026, 2024], FinancialYear::includeStartYear([2026], 2024));
    }
}
