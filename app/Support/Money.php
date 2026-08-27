<?php

namespace App\Support;

class Money
{
    public static function decimals(string $currency): int
    {
        $currency = strtoupper($currency);
        $configured = config('billing.currencies.'.$currency.'.decimals');

        if (is_int($configured)) {
            return $configured;
        }

        return in_array($currency, config('billing.zero_decimal', []), true) ? 0 : 2;
    }

    public static function symbol(string $currency): string
    {
        return config('billing.currencies.'.strtoupper($currency).'.symbol', strtoupper($currency).' ');
    }

    public static function toMinor(int|float|string $amount, string $currency): int
    {
        $decimals = self::decimals($currency);
        $normalized = str_replace([',', ' '], ['', ''], (string) $amount);

        if ($normalized === '' || ! is_numeric($normalized)) {
            return 0;
        }

        return (int) round(((float) $normalized) * (10 ** $decimals));
    }

    public static function fromMinor(int $minor, string $currency): string
    {
        $decimals = self::decimals($currency);

        if ($decimals === 0) {
            return (string) $minor;
        }

        return number_format($minor / (10 ** $decimals), $decimals, '.', '');
    }

    public static function format(int $minor, string $currency): string
    {
        $decimals = self::decimals($currency);
        $value = $decimals === 0
            ? number_format($minor, 0)
            : number_format($minor / (10 ** $decimals), $decimals);

        return self::symbol($currency).$value;
    }
}
