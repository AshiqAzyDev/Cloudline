<?php

namespace Tests\Feature;

use App\Livewire\Clients\Form;
use App\Livewire\Invoices\Form as InvoiceForm;
use App\Models\BillingEntity;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ValidationEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_form_shows_friendly_line_item_errors(): void
    {
        BillingEntity::factory()->create();
        $client = Client::factory()->create();
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(InvoiceForm::class)
            ->set('client_id', $client->id)
            ->set('items.0.description', '')
            ->set('items.0.qty', '1')
            ->set('items.0.unit_price', '10')
            ->call('saveDraft')
            ->assertHasErrors(['items.0.description'])
            ->assertSee('Enter a description for each line item.');
    }

    public function test_client_form_rejects_duplicate_email(): void
    {
        Client::factory()->create(['email' => 'dup@example.test']);
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->set('company', 'Another Co')
            ->set('email', 'dup@example.test')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_user_password_cast_hashes_on_create(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['password' => 'plain-password-value']);

        $this->assertTrue(Hash::isHashed($user->getAttributes()['password']));
        $this->assertTrue(Hash::check('plain-password-value', $user->getAttributes()['password']));
    }
}
