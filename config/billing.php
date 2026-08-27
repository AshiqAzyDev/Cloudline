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

];
