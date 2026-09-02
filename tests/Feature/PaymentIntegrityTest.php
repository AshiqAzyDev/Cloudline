<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_checkout_session_does_not_mark_invoice_paid(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test']);

        $invoice = Invoice::factory()->sent()->create([
            'total_minor' => 50000,
            'amount_paid_minor' => 0,
            'currency' => 'GBP',
            'status' => InvoiceStatus::Sent,
        ]);

        $payload = json_encode([
            'id' => 'evt_unpaid_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_unpaid_1',
                    'object' => 'checkout.session',
                    'client_reference_id' => (string) $invoice->id,
                    'metadata' => ['invoice_id' => (string) $invoice->id],
                    'amount_total' => 50000,
                    'currency' => 'gbp',
                    'payment_status' => 'unpaid',
                    'payment_intent' => 'pi_unpaid_1',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->postStripeWebhook($payload)->assertOk();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
        $this->assertSame(0, $invoice->amount_paid_minor);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_checkout_session_with_amount_mismatch_is_rejected(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test']);

        $invoice = Invoice::factory()->sent()->create([
            'total_minor' => 50000,
            'amount_paid_minor' => 0,
            'currency' => 'GBP',
            'status' => InvoiceStatus::Sent,
        ]);

        $payload = json_encode([
            'id' => 'evt_mismatch_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_mismatch_1',
                    'object' => 'checkout.session',
                    'client_reference_id' => (string) $invoice->id,
                    'metadata' => ['invoice_id' => (string) $invoice->id],
                    'amount_total' => 100,
                    'currency' => 'gbp',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_mismatch_1',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->postStripeWebhook($payload)->assertOk();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
        $this->assertSame(0, $invoice->payments()->count());
    }

    public function test_full_refund_reconciles_paid_amount_and_status(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test']);

        $invoice = Invoice::factory()->sent()->create([
            'total_minor' => 80000,
            'amount_paid_minor' => 80000,
            'currency' => 'GBP',
            'status' => InvoiceStatus::Paid,
            'stripe_payment_intent_id' => 'pi_refund_1',
            'paid_at' => now(),
        ]);

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'amount_minor' => 80000,
            'currency' => 'GBP',
            'fee_minor' => 0,
            'net_minor' => 80000,
            'method' => 'stripe',
            'stripe_payment_intent_id' => 'pi_refund_1',
            'received_at' => now(),
        ]);

        $this->postStripeWebhook($this->refundPayload('evt_refund_1', 80000, 'pi_refund_1'))->assertOk();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Refunded, $invoice->status);
        $this->assertSame(0, $invoice->amount_paid_minor);
        $this->assertNull($invoice->paid_at);
        $this->assertTrue($invoice->payments()->where('method', 'refund')->where('amount_minor', -80000)->exists());
    }

    public function test_partial_refunds_apply_incremental_deltas(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test']);

        $invoice = Invoice::factory()->sent()->create([
            'total_minor' => 80000,
            'amount_paid_minor' => 80000,
            'currency' => 'GBP',
            'status' => InvoiceStatus::Paid,
            'stripe_payment_intent_id' => 'pi_partial_refund',
            'paid_at' => now(),
        ]);

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'amount_minor' => 80000,
            'currency' => 'GBP',
            'fee_minor' => 0,
            'net_minor' => 80000,
            'method' => 'stripe',
            'stripe_payment_intent_id' => 'pi_partial_refund',
            'received_at' => now(),
        ]);

        $this->postStripeWebhook($this->refundPayload('evt_partial_1', 30000, 'pi_partial_refund'))->assertOk();
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame(50000, $invoice->amount_paid_minor);
        $this->assertSame(-30000, (int) $invoice->payments()->where('method', 'refund')->sum('amount_minor'));

        $this->postStripeWebhook($this->refundPayload('evt_partial_2', 80000, 'pi_partial_refund'))->assertOk();
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Refunded, $invoice->status);
        $this->assertSame(0, $invoice->amount_paid_minor);
        $this->assertSame(-80000, (int) $invoice->payments()->where('method', 'refund')->sum('amount_minor'));
        $this->assertSame(2, $invoice->payments()->where('method', 'refund')->count());
    }

    public function test_duplicate_refund_webhook_with_same_cumulative_amount_is_idempotent(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test']);

        $invoice = Invoice::factory()->sent()->create([
            'total_minor' => 80000,
            'amount_paid_minor' => 80000,
            'currency' => 'GBP',
            'status' => InvoiceStatus::Paid,
            'stripe_payment_intent_id' => 'pi_dup_refund',
            'paid_at' => now(),
        ]);

        Payment::query()->create([
            'invoice_id' => $invoice->id,
            'amount_minor' => 80000,
            'currency' => 'GBP',
            'fee_minor' => 0,
            'net_minor' => 80000,
            'method' => 'stripe',
            'stripe_payment_intent_id' => 'pi_dup_refund',
            'received_at' => now(),
        ]);

        $this->postStripeWebhook($this->refundPayload('evt_dup_refund_1', 80000, 'pi_dup_refund'))->assertOk();
        $this->postStripeWebhook($this->refundPayload('evt_dup_refund_2', 80000, 'pi_dup_refund'))->assertOk();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Refunded, $invoice->status);
        $this->assertSame(0, $invoice->amount_paid_minor);
        $this->assertSame(1, $invoice->payments()->where('method', 'refund')->count());
    }

    private function refundPayload(string $eventId, int $amountRefunded, string $paymentIntent = 'pi_refund_1', string $chargeId = 'ch_refund_1'): string
    {
        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => $chargeId,
                    'object' => 'charge',
                    'payment_intent' => $paymentIntent,
                    'amount' => 80000,
                    'amount_refunded' => $amountRefunded,
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    public function test_staff_without_reports_view_cannot_export_csv(): void
    {
        $this->seedRoles();
        Role::findByName('staff')->syncPermissions(
            array_values(array_filter(
                Permissions::staffDefaults(),
                fn (string $permission) => $permission !== Permissions::REPORTS_VIEW,
            )),
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('reports.export', ['fy' => 2026]))
            ->assertForbidden();
    }

    public function test_invoice_update_policy_blocks_non_editable_invoices(): void
    {
        $invoice = Invoice::factory()->sent()->create(['status' => InvoiceStatus::Void]);
        $user = $this->staff();

        $this->actingAs($user)
            ->get(route('invoices.edit', $invoice))
            ->assertForbidden();
    }

    private function postStripeWebhook(string $payload): TestResponse
    {
        $timestamp = time();
        $hmac = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');
        $headers = ['Stripe-Signature' => "t={$timestamp},v1={$hmac}"];

        return $this->call('POST', '/stripe/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $payload);
    }
}
