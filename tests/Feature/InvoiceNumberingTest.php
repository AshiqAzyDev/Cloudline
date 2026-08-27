<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\BillingEntity;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceNumberingService;
use App\Services\InvoiceService;
use App\Services\StripeCheckoutService;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InvoiceNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_are_sequential_per_entity(): void
    {
        Carbon::setTestNow('2026-08-15');

        $entity = BillingEntity::factory()->create([
            'invoice_prefix' => 'CT',
            'next_invoice_number' => 1,
            'numbering_year' => 2026,
        ]);

        $first = Invoice::factory()->create(['billing_entity_id' => $entity->id, 'number' => null]);
        $second = Invoice::factory()->create(['billing_entity_id' => $entity->id, 'number' => null]);

        $service = app(InvoiceNumberingService::class);
        $this->assertSame('CT-2026-0001', $service->allocate($first));
        $this->assertSame('CT-2026-0002', $service->allocate($second));
    }

    public function test_sequence_continues_across_financial_years_with_updated_year_label(): void
    {
        $entity = BillingEntity::factory()->create([
            'invoice_prefix' => 'CT',
            'next_invoice_number' => 40,
            'numbering_year' => 2025,
        ]);

        Carbon::setTestNow('2026-04-01');
        $invoice = Invoice::factory()->create([
            'billing_entity_id' => $entity->id,
            'number' => null,
            'issue_date' => '2026-04-01',
        ]);

        $this->assertSame('CT-2026-0040', app(InvoiceNumberingService::class)->allocate($invoice));
        $this->assertSame(41, $entity->fresh()->next_invoice_number);
        $this->assertSame(2026, FinancialYear::startFor($invoice->issue_date)->year);
    }

    public function test_editing_a_sent_invoice_expires_the_stripe_session(): void
    {
        $stripe = Mockery::mock(StripeCheckoutService::class);
        $stripe->shouldReceive('expireSession')->once()->with('cs_test_123');
        $this->app->instance(StripeCheckoutService::class, $stripe);

        $invoice = Invoice::factory()->sent()->create([
            'stripe_checkout_session_id' => 'cs_test_123',
            'status' => InvoiceStatus::Sent,
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $service = app(InvoiceService::class);
        $service->save([
            'billing_entity_id' => $invoice->billing_entity_id,
            'client_id' => $invoice->client_id,
            'currency' => $invoice->currency,
            'issue_date' => $invoice->issue_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'vat_enabled' => $invoice->vat_enabled,
            'vat_rate' => (float) $invoice->vat_rate,
            'vat_treatment' => $invoice->vat_treatment->value,
            'notes' => 'Updated after send',
            'terms' => $invoice->terms,
        ], [[
            'service_id' => null,
            'description' => 'Revised work',
            'qty' => 1,
            'unit_price' => '50.00',
        ]], $invoice->fresh());

        $invoice->refresh();
        $this->assertNull($invoice->stripe_checkout_session_id);
        $this->assertSame(2, $invoice->revision);
        $this->assertTrue($invoice->events()->where('type', 'edited')->exists());
    }
}
