<?php

namespace App\Support;

use App\Models\BillingEntity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Setting;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Schema;

class CurrencyCatalog
{
    /**
     * @return array<string, array{name: string, symbol: string, decimals: int, bank_only: bool, fx_rate_to_gbp: float|null}>
     */
    public static function all(): array
    {
        if (Schema::hasTable('settings')) {
            $stored = Setting::getValue('currencies');

            if (is_string($stored) && $stored !== '') {
                $decoded = json_decode($stored, true);

                if (is_array($decoded) && $decoded !== []) {
                    return self::normalizeMap($decoded);
                }
            }
        }

        return self::normalizeMap(config('billing.currencies', []));
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function has(string $currency): bool
    {
        return array_key_exists(strtoupper($currency), self::all());
    }

    public static function isBankOnly(string $currency): bool
    {
        $currency = strtoupper($currency);
        $meta = self::all()[$currency] ?? null;

        if ($meta !== null) {
            return (bool) $meta['bank_only'];
        }

        return in_array($currency, config('billing.bank_only_currencies', ['INR']), true);
    }

    public static function fxRateToGbp(string $currency): ?float
    {
        $currency = strtoupper($currency);

        if ($currency === 'GBP') {
            return 1.0;
        }

        $meta = self::all()[$currency] ?? null;

        if ($meta !== null && $meta['fx_rate_to_gbp'] !== null) {
            return (float) $meta['fx_rate_to_gbp'];
        }

        if (Schema::hasTable('settings')) {
            $apiRate = app(ExchangeRateService::class)->rateToGbp($currency);

            if ($apiRate !== null) {
                return $apiRate;
            }
        }

        $rates = config('billing.fx_rates_to_gbp', []);

        return isset($rates[$currency]) ? (float) $rates[$currency] : null;
    }

    /**
     * @return list<array{code: string, name: string, symbol: string, decimals: string, bank_only: bool, fx_rate_to_gbp: string}>
     */
    public static function rows(): array
    {
        return collect(self::all())
            ->map(fn (array $meta, string $code) => [
                'code' => $code,
                'name' => $meta['name'],
                'symbol' => $meta['symbol'],
                'decimals' => (string) $meta['decimals'],
                'bank_only' => $meta['bank_only'],
                'fx_rate_to_gbp' => $meta['fx_rate_to_gbp'] !== null
                    ? rtrim(rtrim(number_format($meta['fx_rate_to_gbp'], 6, '.', ''), '0'), '.')
                    : '',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function saveRows(array $rows): void
    {
        $map = [];

        foreach ($rows as $row) {
            $code = strtoupper((string) ($row['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            $fx = trim((string) ($row['fx_rate_to_gbp'] ?? ''));

            $map[$code] = [
                'name' => trim((string) ($row['name'] ?? $code)),
                'symbol' => (string) ($row['symbol'] ?? $code.' '),
                'decimals' => (int) ($row['decimals'] ?? 2),
                'bank_only' => (bool) ($row['bank_only'] ?? false),
                'fx_rate_to_gbp' => $fx !== '' ? (float) $fx : null,
            ];
        }

        Setting::setValue('currencies', json_encode(self::normalizeMap($map), JSON_THROW_ON_ERROR));
    }

    /**
     * @return list<string>
     */
    public static function inUse(): array
    {
        if (! Schema::hasTable('invoices')) {
            return [];
        }

        return collect([
            Invoice::query()->distinct()->pluck('currency'),
            Client::query()->distinct()->pluck('default_currency'),
            BillingEntity::query()->distinct()->pluck('default_currency'),
            Service::query()->distinct()->pluck('currency'),
            collect([Setting::getValue('default_currency', config('billing.default_currency'))]),
        ])
            ->flatten()
            ->filter()
            ->map(fn ($code) => strtoupper((string) $code))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<string|int, mixed>  $input
     * @return array<string, array{name: string, symbol: string, decimals: int, bank_only: bool, fx_rate_to_gbp: float|null}>
     */
    private static function normalizeMap(array $input): array
    {
        $map = [];

        foreach ($input as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = strtoupper((string) ($row['code'] ?? (is_string($key) ? $key : '')));

            if ($code === '' || strlen($code) !== 3) {
                continue;
            }

            $bankOnly = (bool) ($row['bank_only'] ?? in_array($code, config('billing.bank_only_currencies', ['INR']), true));

            if (array_key_exists('fx_rate_to_gbp', $row)) {
                $fx = ($row['fx_rate_to_gbp'] === '' || $row['fx_rate_to_gbp'] === null)
                    ? null
                    : $row['fx_rate_to_gbp'];
            } else {
                $fx = null;
            }

            $map[$code] = [
                'name' => trim((string) ($row['name'] ?? $code)),
                'symbol' => (string) ($row['symbol'] ?? $code.' '),
                'decimals' => (int) ($row['decimals'] ?? (in_array($code, config('billing.zero_decimal', []), true) ? 0 : 2)),
                'bank_only' => $bankOnly,
                'fx_rate_to_gbp' => $code === 'GBP'
                    ? 1.0
                    : (($fx !== null && $fx !== '') ? (float) $fx : null),
            ];
        }

        ksort($map);

        return $map;
    }
}
