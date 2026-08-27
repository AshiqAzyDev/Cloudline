<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class FinancialYear
{
    public static function startMonth(): int
    {
        return (int) config('billing.fy_start_month', 4);
    }

    public static function startFor(CarbonInterface $date): CarbonImmutable
    {
        $date = CarbonImmutable::parse($date->toDateString())->startOfDay();
        $month = self::startMonth();
        $year = $date->month >= $month ? $date->year : $date->year - 1;

        return $date->setDate($year, $month, 1)->startOfDay();
    }

    public static function endFor(CarbonInterface $date): CarbonImmutable
    {
        return self::startFor($date)->addYear()->subDay()->endOfDay();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function rangeForStartYear(int $startYear): array
    {
        $start = CarbonImmutable::create($startYear, self::startMonth(), 1)->startOfDay();

        return [$start, $start->addYear()->subDay()->endOfDay()];
    }

    public static function label(int $startYear): string
    {
        $endYear = $startYear + 1;

        return sprintf('FY %d/%s', $startYear, substr((string) $endYear, -2));
    }

    /**
     * Financial-year start years available in reports, newest first.
     *
     * Always includes the current FY. When invoice dates are provided, the range
     * expands to cover the earliest and latest invoice financial years.
     *
     * @return list<int>
     */
    public static function availableStartYears(
        ?CarbonInterface $now = null,
        ?int $earliestFromData = null,
        ?int $latestFromData = null,
    ): array {
        $current = self::startFor($now ?? now())->year;
        $earliest = min($earliestFromData ?? $current, $current);
        $latest = max($latestFromData ?? $current, $current);

        $years = [];
        for ($year = $latest; $year >= $earliest; $year--) {
            $years[] = $year;
        }

        return $years;
    }

    /**
     * Ensure a selected FY remains in the option list (e.g. deep-linked URL).
     *
     * @param  list<int>  $years
     * @return list<int>
     */
    public static function includeStartYear(array $years, int $startYear): array
    {
        if (in_array($startYear, $years, true)) {
            return $years;
        }

        $years[] = $startYear;
        rsort($years);

        return array_values($years);
    }
}
