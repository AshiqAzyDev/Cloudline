<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Livewire\Invoices\Form as InvoiceForm;
use App\Models\BillingEntity;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingProductChangesTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_and_invite_routes_are_disabled_in_v1(): void
    {
        $this->get('/portal')->assertNotFound();
        $this->get('/invite/1')->assertNotFound();
    }

    public function test_entity_vat_registered_defaults_on_new_invoice_form(): void
    {
        BillingEntity::factory()->create([
            'name' => 'Cloud Technologies',
            'vat_registered' => true,
            'default_vat_rate' => 20,
            'is_active' => true,
        ]);
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(InvoiceForm::class)
            ->assertSet('vat_enabled', true)
            ->assertSet('vat_rate', '20');
    }

    public function test_entity_not_vat_registered_defaults_vat_off(): void
    {
        BillingEntity::query()->delete();
        BillingEntity::factory()->create([
            'name' => 'Cloud Digital Marketing',
            'vat_registered' => false,
            'default_vat_rate' => 0,
            'is_active' => true,
        ]);
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(InvoiceForm::class)
            ->assertSet('vat_enabled', false)
            ->assertSet('vat_rate', '0');
    }

    public function test_client_bank_payment_sets_awaiting_verification_then_staff_marks_paid(): void
    {
        $invoice = Invoice::factory()->sent()->create([
            'status' => InvoiceStatus::Sent,
            'total_minor' => 12000,
            'amount_paid_minor' => 0,
            'currency' => 'GBP',
        ]);

        $this->post(route('pay.bank', $invoice->pay_token))
            ->assertRedirect(route('pay.show', $invoice->pay_token));

        $this->assertSame(InvoiceStatus::AwaitingVerification, $invoice->fresh()->status);

        app(InvoiceService::class)->markPaidManually($invoice->fresh(), $this->admin());

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
    }

    public function test_inr_invoices_skip_stripe_checkout(): void
    {
        $invoice = Invoice::factory()->sent()->create([
            'currency' => 'INR',
            'status' => InvoiceStatus::Sent,
            'total_minor' => 100000,
        ]);
        $invoice->load(['client', 'billingEntity']);

        $secret = app(StripeCheckoutService::class)->createEmbeddedSession($invoice);

        $this->assertNull($secret);
        $this->assertTrue(app(StripeCheckoutService::class)->isBankOnlyCurrency('INR'));
    }

    public function test_dashboard_loads_for_staff(): void
    {
        Invoice::factory()->sent()->create(['status' => InvoiceStatus::Overdue]);
        Invoice::factory()->create(['status' => InvoiceStatus::Draft]);

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Overdue')
            ->assertSee('Unpaid')
            ->assertSee('Unsent');
    }
}
