<?php

return [

    'fy_start_month' => (int) env('BILLING_FY_START_MONTH', 4),

    'default_currency' => env('BILLING_DEFAULT_CURRENCY', 'GBP'),

    'default_vat_rate' => (float) env('BILLING_DEFAULT_VAT_RATE', 20),

    'default_due_days' => (int) env('BILLING_DEFAULT_DUE_DAYS', 14),

    'currencies' => [
        'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'decimals' => 2],
        'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹', 'decimals' => 2],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimals' => 2],
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
        'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'decimals' => 2],
        'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'decimals' => 2],
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'decimals' => 0],
        'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩', 'decimals' => 0],
    ],

    'zero_decimal' => ['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'],

    /**
     * Currencies that must be settled by bank transfer (no Stripe Checkout).
     *
     * @var list<string>
     */
    'bank_only_currencies' => ['INR'],

    /**
     * Indicative FX rates: 1 unit of foreign currency equals this many GBP.
     * Used for dashboard/report GBP estimates before Stripe settlement.
     *
     * @var array<string, float>
     */
    'fx_rates_to_gbp' => [
        'EUR' => 0.85,
        'USD' => 0.79,
        'INR' => 0.0095,
        'AUD' => 0.52,
        'CAD' => 0.58,
    ],

    'exchangerate_api' => [
        'key' => env('EXCHANGERATE_API_KEY'),
        'base' => env('EXCHANGERATE_API_BASE', 'GBP'),
        'verify_ssl' => filter_var(env('EXCHANGERATE_API_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    ],

];
