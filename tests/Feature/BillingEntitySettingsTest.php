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

    public function test_saving_entities_stores_structured_address_and_bank_fields(): void
    {
        $entity = BillingEntity::factory()->create([
            'default_due_days' => 14,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Index::class)
            ->set('tab', 'entities')
            ->set('entities.0.address_line1', '10 Downing Street')
            ->set('entities.0.city', 'London')
            ->set('entities.0.postcode', 'SW1A 2AA')
            ->set('entities.0.country', 'United Kingdom')
            ->set('entities.0.bank_name', 'Barclays')
            ->set('entities.0.account_name', $entity->legal_name)
            ->set('entities.0.sort_code', '20-00-00')
            ->set('entities.0.account_number', '11223344')
            ->call('saveEntities')
            ->assertHasNoErrors();

        $entity->refresh();

        $this->assertSame('10 Downing Street', $entity->address_line1);
        $this->assertSame('SW1A 2AA', $entity->postcode);
        $this->assertSame('Barclays', $entity->bank_name);
        $this->assertSame('20-00-00', $entity->sort_code);
        $this->assertStringContainsString('10 Downing Street', (string) $entity->address);
        $this->assertStringContainsString('Sort code: 20-00-00', (string) $entity->bank_details);
    }
}
