<?php

namespace App\Support;

use App\Models\BillingEntity;
use App\Models\Setting;

class BillingDefaults
{
    public static function dueDays(?BillingEntity $entity = null): int
    {
        if ($entity?->default_due_days !== null) {
            return (int) $entity->default_due_days;
        }

        return (int) Setting::getValue('default_due_days', config('billing.default_due_days', 14));
    }

    public static function vatRate(?BillingEntity $entity = null): float
    {
        if ($entity && $entity->default_vat_rate !== null) {
            return (float) $entity->default_vat_rate;
        }

        return (float) Setting::getValue('default_vat_rate', config('billing.default_vat_rate', 20));
    }

    public static function currency(?BillingEntity $entity = null): string
    {
        if ($entity?->default_currency) {
            return $entity->default_currency;
        }

        return (string) Setting::getValue('default_currency', config('billing.default_currency', 'GBP'));
    }

    /**
     * Indicative FX rate to GBP (1 unit of currency = X GBP). Null when unknown.
     */
    public static function fxRateToGbp(string $currency): ?float
    {
        return CurrencyCatalog::fxRateToGbp($currency);
    }

    public static function indicativeGbpMinor(int $amountMinor, string $currency): ?int
    {
        $rate = self::fxRateToGbp($currency);

        if ($rate === null) {
            return null;
        }

        return (int) round($amountMinor * $rate);
    }
}
