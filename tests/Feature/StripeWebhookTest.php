<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\StripeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_signature_is_rejected(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test']);

        $this->postJson('/stripe/webhook', ['hello' => 'world'], [
            'Stripe-Signature' => 't=1,v1=invalid',
        ])->assertStatus(400);
    }

    public function test_completed_checkout_marks_invoice_paid_and_is_idempotent(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test']);

        $invoice = Invoice::factory()->sent()->create([
            'total_minor' => 120000,
            'amount_paid_minor' => 0,
            'status' => InvoiceStatus::Sent,
        ]);

        $payload = $this->eventPayload('evt_test_1', $invoice->id, 120000);
        $headers = ['Stripe-Signature' => $this->signature($payload)];

        $this->call('POST', '/stripe/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $payload)
            ->assertOk();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(120000, $invoice->amount_paid_minor);
        $this->assertSame(1, $invoice->payments()->count());

        $this->call('POST', '/stripe/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $payload)
            ->assertOk();

        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(1, StripeEvent::query()->where('event_id', 'evt_test_1')->count());
    }

    private function eventPayload(string $eventId, int $invoiceId, int $amount): string
    {
        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_1',
                    'object' => 'checkout.session',
                    'client_reference_id' => (string) $invoiceId,
                    'metadata' => ['invoice_id' => (string) $invoiceId],
                    'amount_total' => $amount,
                    'currency' => 'gbp',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_1',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function signature(string $payload): string
    {
        $timestamp = time();
        $hmac = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        return "t={$timestamp},v1={$hmac}";
    }
}
