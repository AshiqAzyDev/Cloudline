<?php

namespace Tests\Feature;

use App\Livewire\Settings\Index;
use App\Models\Invoice;
use App\Models\Setting;
use App\Support\CurrencyCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_falls_back_to_config_when_no_setting(): void
    {
        $this->assertTrue(CurrencyCatalog::has('GBP'));
        $this->assertTrue(CurrencyCatalog::has('EUR'));
        $this->assertSame('£', CurrencyCatalog::all()['GBP']['symbol']);
    }

    public function test_admin_can_add_a_currency_from_settings(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'currencies')
            ->call('addCurrencyRow');

        $index = count($component->get('currencyRows')) - 1;

        $component
            ->set("currencyRows.{$index}.code", 'SGD')
            ->set("currencyRows.{$index}.name", 'Singapore Dollar')
            ->set("currencyRows.{$index}.symbol", 'S$')
            ->set("currencyRows.{$index}.decimals", '2')
            ->set("currencyRows.{$index}.fx_rate_to_gbp", '0.58')
            ->call('saveCurrencies')
            ->assertHasNoErrors();

        $this->assertTrue(CurrencyCatalog::has('SGD'));
        $this->assertSame(0.58, CurrencyCatalog::fxRateToGbp('SGD'));
    }

    public function test_cannot_remove_a_currency_that_is_in_use(): void
    {
        Invoice::factory()->sent()->create(['currency' => 'EUR']);

        $rows = CurrencyCatalog::rows();
        $index = collect($rows)->search(fn ($row) => $row['code'] === 'EUR');

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'currencies')
            ->call('removeCurrencyRow', $index)
            ->assertHasErrors('currencyRows');
    }

    public function test_saved_currencies_persist_in_settings_table(): void
    {
        CurrencyCatalog::saveRows([
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED ', 'decimals' => '2', 'bank_only' => true, 'fx_rate_to_gbp' => '0.21'],
        ]);

        $this->assertTrue(CurrencyCatalog::has('AED'));
        $this->assertTrue(CurrencyCatalog::isBankOnly('AED'));
        $this->assertNotNull(Setting::query()->where('key', 'currencies')->value('value'));
    }
}
