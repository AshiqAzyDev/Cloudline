<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\ExchangeRateService;
use App\Services\InvoiceService;
use App\Support\CurrencyCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_stores_gbp_conversion_rates_from_api(): void
    {
        config(['billing.exchangerate_api.key' => 'test-key', 'billing.exchangerate_api.base' => 'GBP']);

        Http::fake([
            'v6.exchangerate-api.com/v6/test-key/latest/GBP' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
                'time_next_update_unix' => time() + 86400,
                'conversion_rates' => [
                    'GBP' => 1,
                    'USD' => 1.27,
                    'EUR' => 1.17,
                ],
            ]),
        ]);

        $service = app(ExchangeRateService::class);
        $rates = $service->refresh();

        $this->assertSame(1.0, $rates['GBP']);
        $this->assertEqualsWithDelta(1 / 1.27, $rates['USD'], 0.000001);
        $this->assertEqualsWithDelta(1 / 1.17, $rates['EUR'], 0.000001);
        $this->assertNotNull($service->lastUpdatedAt());
        $this->assertEqualsWithDelta(1 / 1.27, $service->rateToGbp('USD'), 0.000001);
    }

    public function test_catalog_uses_api_rate_when_manual_fx_is_empty(): void
    {
        config(['billing.exchangerate_api.key' => 'test-key']);

        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
                'time_next_update_unix' => time() + 86400,
                'conversion_rates' => [
                    'GBP' => 1,
                    'USD' => 1.25,
                ],
            ]),
        ]);

        CurrencyCatalog::saveRows([
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimals' => '2', 'bank_only' => false, 'fx_rate_to_gbp' => ''],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => '2', 'bank_only' => false, 'fx_rate_to_gbp' => ''],
        ]);

        app(ExchangeRateService::class)->refresh();

        $this->assertEqualsWithDelta(0.8, CurrencyCatalog::fxRateToGbp('USD'), 0.000001);
    }

    public function test_manual_fx_rate_overrides_api_rate(): void
    {
        config(['billing.exchangerate_api.key' => 'test-key']);

        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
                'time_next_update_unix' => time() + 86400,
                'conversion_rates' => ['GBP' => 1, 'USD' => 1.25],
            ]),
        ]);

        CurrencyCatalog::saveRows([
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimals' => '2', 'bank_only' => false, 'fx_rate_to_gbp' => '1'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => '2', 'bank_only' => false, 'fx_rate_to_gbp' => '0.77'],
        ]);

        app(ExchangeRateService::class)->refresh();

        $this->assertSame(0.77, CurrencyCatalog::fxRateToGbp('USD'));
    }

    public function test_ensure_fresh_refreshes_when_rates_are_stale(): void
    {
        config(['billing.exchangerate_api.key' => 'test-key']);

        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
                'time_next_update_unix' => time() + 86400,
                'conversion_rates' => ['GBP' => 1, 'USD' => 1.25],
            ]),
        ]);

        $service = app(ExchangeRateService::class);

        $this->assertTrue($service->isStale());

        $service->ensureFresh();

        Http::assertSentCount(1);
        $this->assertFalse($service->isStale());
        $this->assertEqualsWithDelta(0.8, $service->rateToGbp('USD'), 0.000001);
    }

    public function test_ensure_fresh_skips_api_when_rates_are_current(): void
    {
        config(['billing.exchangerate_api.key' => 'test-key']);

        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
                'time_next_update_unix' => time() + 86400,
                'conversion_rates' => ['GBP' => 1, 'USD' => 1.25],
            ]),
        ]);

        $service = app(ExchangeRateService::class);
        $service->refresh();

        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
                'time_next_update_unix' => time() + 86400,
                'conversion_rates' => ['GBP' => 1, 'USD' => 2],
            ]),
        ]);

        $service->ensureFresh();

        Http::assertNothingSent();
        $this->assertEqualsWithDelta(0.8, $service->rateToGbp('USD'), 0.000001);
    }

    public function test_invoice_send_refreshes_stale_rates_before_syncing_gbp(): void
    {
        config(['billing.exchangerate_api.key' => 'test-key']);

        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
                'time_next_update_unix' => time() + 86400,
                'conversion_rates' => ['GBP' => 1, 'USD' => 1.25],
            ]),
        ]);

        CurrencyCatalog::saveRows([
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimals' => '2', 'bank_only' => false, 'fx_rate_to_gbp' => '1'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => '2', 'bank_only' => false, 'fx_rate_to_gbp' => ''],
        ]);

        Mail::fake();

        $invoice = Invoice::factory()->create([
            'currency' => 'USD',
            'total_minor' => 10000,
            'status' => InvoiceStatus::Draft,
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);
        $invoice->client->update(['email' => 'client@example.com']);

        app(InvoiceService::class)->send($invoice->fresh());

        Http::assertSentCount(1);

        $invoice->refresh();
        $this->assertEqualsWithDelta(0.8, (float) $invoice->fx_rate_to_gbp, 0.000001);
        $this->assertSame(8000, $invoice->total_gbp_minor);
    }

    public function test_config_catalog_does_not_mask_api_fx_rates(): void
    {
        config(['billing.exchangerate_api.key' => 'test-key']);

        Http::fake([
            'v6.exchangerate-api.com/*' => Http::response([
                'result' => 'success',
                'time_last_update_utc' => 'Fri, 27 Mar 2020 00:00:00 +0000',
                'time_next_update_unix' => time() + 86400,
                'conversion_rates' => ['GBP' => 1, 'USD' => 1.25],
            ]),
        ]);

        app(ExchangeRateService::class)->refresh();

        $this->assertEqualsWithDelta(0.8, CurrencyCatalog::fxRateToGbp('USD'), 0.000001);
    }
}
