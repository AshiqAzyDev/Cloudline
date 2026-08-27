<?php

namespace App\Services;

use App\Enums\InvoiceEventType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\StripeEvent;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutService
{
    public function client(): ?StripeClient
    {
        $secret = config('stripe.secret');

        return $secret ? new StripeClient($secret) : null;
    }

    public function isConfigured(): bool
    {
        return filled(config('stripe.secret')) && filled(config('stripe.key'));
    }

    public function createEmbeddedSession(Invoice $invoice): ?string
    {
        $stripe = $this->client();

        if (! $stripe || ! $invoice->isPayable()) {
            return null;
        }

        if ($this->isBankOnlyCurrency($invoice->currency)) {
            return null;
        }

        if (! $invoice->client?->email) {
            Log::warning('Stripe checkout skipped: invoice client has no email', ['invoice_id' => $invoice->id]);

            return null;
        }

        if ($invoice->outstandingMinor() < 1) {
            return null;
        }

        try {
            if ($invoice->stripe_checkout_session_id) {
                try {
                    $existing = $stripe->checkout->sessions->retrieve($invoice->stripe_checkout_session_id);
                    if ($existing->status === 'open' && $existing->client_secret) {
                        return $existing->client_secret;
                    }
                } catch (\Throwable) {
                    // Create a fresh session below.
                }
            }

            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'ui_mode' => config('stripe.ui_mode', 'embedded_page'),
                'client_reference_id' => (string) $invoice->id,
                'customer_email' => $invoice->client->email,
                'return_url' => route('pay.complete', $invoice->pay_token).'?session_id={CHECKOUT_SESSION_ID}',
                'metadata' => [
                    'invoice_id' => (string) $invoice->id,
                    'invoice_number' => $invoice->displayNumber(),
                    'entity' => $invoice->billingEntity?->slug ?? '',
                ],
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($invoice->currency),
                        'unit_amount' => $invoice->outstandingMinor(),
                        'product_data' => [
                            'name' => 'Invoice '.$invoice->displayNumber(),
                            'description' => $invoice->client->company,
                        ],
                    ],
                ]],
            ]);

            $invoice->forceFill([
                'stripe_checkout_session_id' => $session->id,
            ])->save();

            return $session->client_secret;
        } catch (\Throwable $e) {
            Log::error('Unable to create Stripe checkout session', [
                'invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function expireSession(?string $sessionId): void
    {
        if (! $sessionId || ! $this->client()) {
            return;
        }

        try {
            $this->client()->checkout->sessions->expire($sessionId);
        } catch (\Throwable $e) {
            Log::info('Unable to expire Stripe session', [
                'session' => $sessionId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent.latest_charge.balance_transaction'],
        ]);
    }

    public function constructEvent(string $payload, string $signature): Event
    {
        $secret = config('stripe.webhook_secret');

        if (! $secret) {
            throw new SignatureVerificationException('Webhook secret is not configured.');
        }

        return Webhook::constructEvent($payload, $signature, $secret);
    }

    public function handleEvent(Event $event): void
    {
        $record = StripeEvent::query()->firstOrCreate(
            ['event_id' => $event->id],
            ['type' => $event->type, 'payload' => $event->toArray()],
        );

        if ($record->processed_at) {
            return;
        }

        match ($event->type) {
            'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $this->handleCheckoutCompleted($event),
            'checkout.session.async_payment_failed', 'payment_intent.payment_failed' => null,
            'charge.refunded' => $this->handleRefund($event),
            default => null,
        };

        $record->forceFill(['processed_at' => now(), 'type' => $event->type])->save();
    }

    private function handleCheckoutCompleted(Event $event): void
    {
        /** @var Session $session */
        $session = $event->data->object;
        $invoice = $this->invoiceFromSession($session);

        if (! $invoice || $invoice->status === InvoiceStatus::Paid) {
            return;
        }

        $details = $this->paymentDetailsFromSession($session);

        app(InvoiceService::class)->applyPayment($invoice, $details, InvoiceEventType::PaymentSucceeded);

        if ($invoice->currency !== 'GBP' && ($details['settlement_amount_minor'] ?? null) && ($details['settlement_currency'] ?? null) === 'gbp') {
            $invoice->forceFill([
                'total_gbp_minor' => $details['settlement_amount_minor'],
                'stripe_payment_intent_id' => $details['stripe_payment_intent_id'] ?? $invoice->stripe_payment_intent_id,
            ])->save();
        } else {
            $invoice->forceFill([
                'stripe_payment_intent_id' => $details['stripe_payment_intent_id'] ?? $invoice->stripe_payment_intent_id,
            ])->save();
        }
    }

    private function handleRefund(Event $event): void
    {
        $charge = $event->data->object;
        $paymentIntent = $charge->payment_intent ?? null;

        if (! $paymentIntent) {
            return;
        }

        $invoice = Invoice::query()->where('stripe_payment_intent_id', $paymentIntent)->first();

        if (! $invoice) {
            return;
        }

        $amount = (int) ($charge->amount ?? 0);
        $refunded = (int) ($charge->amount_refunded ?? 0);
        $fullyRefunded = $amount > 0 && $refunded >= $amount;

        if ($fullyRefunded) {
            $invoice->status = InvoiceStatus::Refunded;
            $invoice->save();
            $invoice->recordEvent(InvoiceEventType::Refunded, [
                'charge' => $charge->id ?? null,
                'amount_refunded' => $refunded,
            ]);

            return;
        }

        $invoice->recordEvent(InvoiceEventType::Refunded, [
            'charge' => $charge->id ?? null,
            'partial' => true,
            'amount_refunded' => $refunded,
            'amount' => $amount,
        ]);
    }

    private function invoiceFromSession(Session $session): ?Invoice
    {
        $id = $session->metadata['invoice_id'] ?? $session->client_reference_id ?? null;

        return $id ? Invoice::query()->with(['client', 'billingEntity'])->find($id) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentDetailsFromSession(Session $session): array
    {
        $intent = $session->payment_intent;
        $charge = is_object($intent) ? $intent->latest_charge : null;
        $balance = is_object($charge) ? $charge->balance_transaction : null;

        $amount = (int) ($session->amount_total ?? 0);
        $fee = is_object($balance) ? (int) $balance->fee : 0;
        $net = is_object($balance) ? (int) $balance->net : $amount - $fee;

        return [
            'stripe_payment_intent_id' => is_object($intent) ? $intent->id : $intent,
            'stripe_charge_id' => is_object($charge) ? $charge->id : $charge,
            'stripe_balance_transaction_id' => is_object($balance) ? $balance->id : null,
            'amount_minor' => $amount,
            'currency' => strtoupper((string) ($session->currency ?? 'gbp')),
            'fee_minor' => $fee,
            'net_minor' => $net,
            'settlement_currency' => is_object($balance) ? $balance->currency : null,
            'settlement_amount_minor' => is_object($balance) ? (int) $balance->amount : null,
            'method' => 'stripe',
            'received_at' => now(),
        ];
    }

    public function lastWebhookAt(): ?string
    {
        return StripeEvent::query()->latest('processed_at')->value('processed_at');
    }

    public function isBankOnlyCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), config('billing.bank_only_currencies', ['INR']), true);
    }
}
