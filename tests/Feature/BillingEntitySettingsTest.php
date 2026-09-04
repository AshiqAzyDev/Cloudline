<?php

namespace Tests\Feature;

use App\Livewire\Settings\Index;
use App\Models\BillingEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingEntitySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_an_entity_stores_structured_address_and_bank_fields(): void
    {
        $entity = BillingEntity::factory()->create([
            'default_due_days' => 14,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'entities')
            ->call('editEntity', $entity->id)
            ->set('entityForm.address_line1', '10 Downing Street')
            ->set('entityForm.city', 'London')
            ->set('entityForm.postcode', 'SW1A 2AA')
            ->set('entityForm.country', 'United Kingdom')
            ->set('entityForm.bank_name', 'Barclays')
            ->set('entityForm.account_name', $entity->legal_name)
            ->set('entityForm.sort_code', '20-00-00')
            ->set('entityForm.account_number', '11223344')
            ->call('saveEntity')
            ->assertHasNoErrors();

        $entity->refresh();

        $this->assertSame('10 Downing Street', $entity->address_line1);
        $this->assertSame('SW1A 2AA', $entity->postcode);
        $this->assertSame('Barclays', $entity->bank_name);
        $this->assertSame('20-00-00', $entity->sort_code);
        $this->assertStringContainsString('10 Downing Street', (string) $entity->address);
        $this->assertStringContainsString('Sort code: 20-00-00', (string) $entity->bank_details);
    }

    public function test_entities_tab_lists_entities_and_opens_one_form_at_a_time(): void
    {
        $alpha = BillingEntity::factory()->create([
            'name' => 'Alpha Brand',
            'email' => 'alpha@example.test',
            'invoice_prefix' => 'ALP',
        ]);
        BillingEntity::factory()->create([
            'name' => 'Beta Brand',
            'email' => 'beta@example.test',
            'invoice_prefix' => 'BET',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'entities')
            ->assertSee('Alpha Brand')
            ->assertSee('Beta Brand')
            ->assertDontSee('wire:model="entityForm.email"', false)
            ->call('editEntity', $alpha->id)
            ->assertSet('editingEntityId', $alpha->id)
            ->assertSee('wire:model="entityForm.email"', false)
            ->assertDontSee('Beta Brand');
    }

    public function test_entity_search_hides_non_matching_rows(): void
    {
        BillingEntity::factory()->create([
            'name' => 'Northwind Trading',
            'email' => 'north@example.test',
            'invoice_prefix' => 'NWT',
        ]);
        BillingEntity::factory()->create([
            'name' => 'Southpark Media',
            'email' => 'south@example.test',
            'invoice_prefix' => 'SPM',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'entities')
            ->set('entitySearch', 'Southpark')
            ->assertSee('Southpark Media')
            ->assertDontSee('Northwind Trading');
    }

    public function test_inactive_status_filter_hides_active_entities(): void
    {
        BillingEntity::factory()->create([
            'name' => 'Active Brand',
            'invoice_prefix' => 'ACT',
            'is_active' => true,
        ]);
        BillingEntity::factory()->create([
            'name' => 'Inactive Brand',
            'invoice_prefix' => 'INA',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'entities')
            ->set('entityStatus', 'inactive')
            ->assertSee('Inactive Brand')
            ->assertDontSee('Active Brand');
    }

    public function test_entity_list_paginates_after_twenty_rows(): void
    {
        foreach (range(0, 20) as $index) {
            $label = sprintf('Entity %02d', $index);
            BillingEntity::factory()->create([
                'name' => $label,
                'legal_name' => $label.' Ltd',
                'invoice_prefix' => sprintf('E%02d', $index),
            ]);
        }

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'entities')
            ->assertSee('Entity 00')
            ->assertDontSee('Entity 20')
            ->call('gotoPage', 2)
            ->assertSee('Entity 20')
            ->assertDontSee('Entity 00');
    }

    public function test_creating_an_entity_opens_the_new_entity(): void
    {
        BillingEntity::factory()->create([
            'name' => 'Existing Brand',
            'invoice_prefix' => 'EXB',
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'entities')
            ->call('startCreateEntity')
            ->assertSet('showCreateEntity', true)
            ->assertSee('Create entity')
            ->set('newEntity.name', 'New Co')
            ->set('newEntity.legal_name', 'New Co Ltd')
            ->set('newEntity.email', 'billing@newco.test')
            ->set('newEntity.invoice_prefix', 'NCO')
            ->set('newEntity.default_currency', 'GBP')
            ->call('addEntity')
            ->assertHasNoErrors()
            ->assertSet('showCreateEntity', false);

        $created = BillingEntity::query()->where('invoice_prefix', 'NCO')->first();

        $this->assertNotNull($created);
        $component->assertSet('editingEntityId', $created->id);
        $this->assertSame('New Co', $created->name);
        $this->assertSame('billing@newco.test', $created->email);
    }

    public function test_saving_does_not_change_another_entity(): void
    {
        $alpha = BillingEntity::factory()->create([
            'name' => 'Alpha Brand',
            'email' => 'alpha@example.test',
            'invoice_prefix' => 'ALP',
        ]);
        $beta = BillingEntity::factory()->create([
            'name' => 'Beta Brand',
            'email' => 'beta@example.test',
            'invoice_prefix' => 'BET',
        ]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'entities')
            ->call('editEntity', $beta->id)
            ->set('entityForm.email', 'beta-new@example.test')
            ->call('saveEntity')
            ->assertHasNoErrors();

        $this->assertSame('alpha@example.test', $alpha->fresh()->email);
        $this->assertSame('beta-new@example.test', $beta->fresh()->email);
    }
}
