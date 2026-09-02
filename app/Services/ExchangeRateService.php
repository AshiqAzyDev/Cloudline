<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ExchangeRateService
{
    private const CACHE_KEY = 'fx_rates_to_gbp';

    public function isConfigured(): bool
    {
        return filled(config('billing.exchangerate_api.key'));
    }

    /**
     * Indicative rates: 1 unit of foreign currency equals this many GBP.
     *
     * @return array<string, float>
     */
    public function ratesToGbp(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            $stored = Setting::getValue('fx_rates_api');

            if (! is_string($stored) || $stored === '') {
                return [];
            }

            $decoded = json_decode($stored, true);

            return is_array($decoded) ? $this->normalizeRates($decoded) : [];
        });
    }

    public function rateToGbp(string $currency): ?float
    {
        $currency = strtoupper($currency);

        if ($currency === 'GBP') {
            return 1.0;
        }

        $rates = $this->ratesToGbp();

        return isset($rates[$currency]) ? (float) $rates[$currency] : null;
    }

    public function lastUpdatedAt(): ?Carbon
    {
        if (! Schema::hasTable('settings')) {
            return null;
        }

        $value = Setting::getValue('fx_rates_api_updated_at');

        return filled($value) ? Carbon::parse($value) : null;
    }

    public function isStale(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        if ($this->lastUpdatedAt() === null) {
            return true;
        }

        $nextUnix = Setting::getValue('fx_rates_api_next_update_unix');

        if (is_numeric($nextUnix)) {
            return time() >= (int) $nextUnix;
        }

        return $this->lastUpdatedAt()->lt(now()->subHours(12));
    }

    /**
     * Refresh FX rates from the API when stale. Safe to call before saves and sends.
     */
    public function ensureFresh(): void
    {
        if (! $this->isConfigured() || ! $this->isStale()) {
            return;
        }

        try {
            $this->refresh();
        } catch (\Throwable $e) {
            if ($this->ratesToGbp() === []) {
                throw $e;
            }

            Log::warning('Exchange rate refresh failed; using last stored rates.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch latest rates from ExchangeRate-API (base GBP) and persist them.
     *
     * @return array<string, float>
     */
    public function refresh(): array
    {
        $key = config('billing.exchangerate_api.key');
        $base = strtoupper((string) config('billing.exchangerate_api.base', 'GBP'));

        if (! $key) {
            throw new RuntimeException('EXCHANGERATE_API_KEY is not configured.');
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->withOptions(['verify' => (bool) config('billing.exchangerate_api.verify_ssl', true)])
            ->get("https://v6.exchangerate-api.com/v6/{$key}/latest/{$base}");

        if (! $response->successful()) {
            throw new RuntimeException('Exchange rate API request failed.');
        }

        $payload = $response->json();

        if (($payload['result'] ?? null) !== 'success') {
            $type = $payload['error-type'] ?? 'unknown';

            throw new RuntimeException("Exchange rate API error: {$type}");
        }

        $conversionRates = $payload['conversion_rates'] ?? [];

        if (! is_array($conversionRates) || $conversionRates === []) {
            throw new RuntimeException('Exchange rate API returned no conversion rates.');
        }

        $rates = $this->convertBaseRatesToGbp($base, $conversionRates);
        $updatedAt = $payload['time_last_update_utc'] ?? now()->toIso8601String();

        Setting::setValue('fx_rates_api', json_encode($rates, JSON_THROW_ON_ERROR));
        Setting::setValue('fx_rates_api_updated_at', $updatedAt);

        if (isset($payload['time_next_update_unix']) && is_numeric($payload['time_next_update_unix'])) {
            Setting::setValue('fx_rates_api_next_update_unix', (string) (int) $payload['time_next_update_unix']);
        }

        Cache::forget(self::CACHE_KEY);
        Cache::put(self::CACHE_KEY, $rates, $this->cacheUntil($payload));

        return $rates;
    }

    /**
     * @param  array<string, float|int>  $conversionRates
     * @return array<string, float>
     */
    private function convertBaseRatesToGbp(string $base, array $conversionRates): array
    {
        $rates = ['GBP' => 1.0];

        if ($base === 'GBP') {
            foreach ($conversionRates as $code => $unitsPerGbp) {
                $code = strtoupper((string) $code);

                if ($code === 'GBP' || (float) $unitsPerGbp <= 0) {
                    continue;
                }

                $rates[$code] = round(1 / (float) $unitsPerGbp, 8);
            }

            return $this->normalizeRates($rates);
        }

        $gbpPerBase = isset($conversionRates['GBP']) ? (float) $conversionRates['GBP'] : null;

        if ($gbpPerBase === null || $gbpPerBase <= 0) {
            throw new RuntimeException("Exchange rate API response did not include GBP conversion for base {$base}.");
        }

        foreach ($conversionRates as $code => $unitsPerBase) {
            $code = strtoupper((string) $code);
            $unitsPerBase = (float) $unitsPerBase;

            if ($unitsPerBase <= 0) {
                continue;
            }

            $rates[$code] = round($gbpPerBase / $unitsPerBase, 8);
        }

        return $this->normalizeRates($rates);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function cacheUntil(array $payload): Carbon
    {
        $next = $payload['time_next_update_unix'] ?? null;

        if (is_numeric($next) && (int) $next > time()) {
            return Carbon::createFromTimestamp((int) $next);
        }

        return now()->addHours(12);
    }

    /**
     * @param  array<string, mixed>  $rates
     * @return array<string, float>
     */
    private function normalizeRates(array $rates): array
    {
        $normalized = [];

        foreach ($rates as $code => $rate) {
            $code = strtoupper((string) $code);
            $normalized[$code] = (float) $rate;
        }

        ksort($normalized);

        return $normalized;
    }
}
