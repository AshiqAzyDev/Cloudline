<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class RefreshExchangeRates extends Command
{
    protected $signature = 'billing:refresh-exchange-rates';

    protected $description = 'Fetch indicative FX rates to GBP from ExchangeRate-API';

    public function handle(ExchangeRateService $rates): int
    {
        if (! $rates->isConfigured()) {
            $this->warn('EXCHANGERATE_API_KEY is not set — skipping.');

            return self::SUCCESS;
        }

        try {
            $fresh = $rates->refresh();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Updated '.count($fresh).' currency rates.');
        if ($updated = $rates->lastUpdatedAt()) {
            $this->line('API rates as of '.$updated->toDayDateTimeString());
        }

        return self::SUCCESS;
    }
}
