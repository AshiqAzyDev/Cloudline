<?php

namespace App\Services;

use App\Enums\InvoiceEventType;
use App\Enums\InvoiceStatus;
use App\Enums\VatTreatment;
use App\Mail\InvoiceReminderMail;
use App\Mail\InvoiceSentMail;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private InvoiceNumberingService $numbering,
        private StripeCheckoutService $stripe,
        private ReminderService $reminders,
    ) {}

    /**
     * @param  array<int, array{service_id?: mixed, description: string, qty: mixed, unit_price: mixed}>  $items
     */
    public function save(array $attributes, array $items, ?Invoice $invoice = null, ?User $user = null): Invoice
    {
        return DB::transaction(function () use ($attributes, $items, $invoice, $user) {
            $wasSent = $invoice && $invoice->status !== InvoiceStatus::Draft;
            $before = $invoice?->only(['client_id', 'currency', 'due_date', 'total_minor', 'notes']);

            if ($invoice && ! $invoice->isEditable() && $invoice->status !== InvoiceStatus::Paid) {
                throw ValidationException::withMessages(['invoice' => 'This invoice can no longer be edited.']);
            }

            if ($invoice && $invoice->status === InvoiceStatus::Paid) {
                $invoice->update(['notes' => $attributes['notes'] ?? $invoice->notes]);

                return $invoice->refresh();
            }

            $totals = $this->calculateTotals(
                $items,
                $attributes['currency'],
                (bool) ($attributes['vat_enabled'] ?? false),
                (float) ($attributes['vat_rate'] ?? 0),
                VatTreatment::from($attributes['vat_treatment'] ?? VatTreatment::Standard->value),
            );

            $payload = array_merge($attributes, $totals, [
                'created_by' => $invoice?->created_by ?? $user?->id,
            ]);

            if (! $invoice) {
                $invoice = Invoice::query()->create($payload);
                $invoice->recordEvent(InvoiceEventType::Created, [], $user);
            } else {
                $oldSession = $invoice->stripe_checkout_session_id;
                $invoice->fill($payload);

                if ($wasSent) {
                    $invoice->revision = $invoice->revision + 1;
                    $invoice->stripe_checkout_session_id = null;
                }

                $invoice->save();

                if ($wasSent) {
                    $this->stripe->expireSession($oldSession);
                    $invoice->recordEvent(InvoiceEventType::Edited, [
                        'before' => $before,
                        'after' => $invoice->only(['client_id', 'currency', 'due_date', 'total_minor', 'notes']),
                        'revision' => $invoice->revision,
                    ], $user);
                    $this->reminders->reschedule($invoice);
                }

                $invoice->items()->delete();
            }

            foreach (array_values($items) as $index => $item) {
                $qty = (float) ($item['qty'] ?: 0);
                $unit = Money::toMinor($item['unit_price'] ?? 0, $invoice->currency);

                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'service_id' => $item['service_id'] ?: null,
                    'description' => $item['description'],
                    'qty' => $qty,
                    'unit_price_minor' => $unit,
                    'amount_minor' => (int) round($qty * $unit),
                    'sort_order' => $index,
                ]);
            }

            return $invoice->refresh()->load(['items', 'client', 'billingEntity']);
        });
    }

    /**
     * @param  array<int, array{qty: mixed, unit_price: mixed}>  $items
     * @return array{subtotal_minor: int, vat_minor: int, total_minor: int, vat_enabled: bool, vat_rate: float}
     */
    public function calculateTotals(array $items, string $currency, bool $vatEnabled, float $vatRate, VatTreatment $treatment): array
    {
        if (! $treatment->appliesVat()) {
            $vatEnabled = false;
        }

        $subtotal = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?: 0);
            $unit = Money::toMinor($item['unit_price'] ?? 0, $currency);
            $subtotal += (int) round($qty * $unit);
        }

        $vat = $vatEnabled ? (int) round($subtotal * ($vatRate / 100)) : 0;

        return [
            'subtotal_minor' => $subtotal,
            'vat_enabled' => $vatEnabled,
            'vat_rate' => $vatEnabled ? $vatRate : 0,
            'vat_minor' => $vat,
            'total_minor' => $subtotal + $vat,
        ];
    }

    public function send(Invoice $invoice, ?User $user = null): Invoice
    {
        if ($invoice->status === InvoiceStatus::Void) {
            throw ValidationException::withMessages(['invoice' => 'A voided invoice cannot be sent.']);
        }

        $invoice->loadMissing('client');

        if (blank($invoice->client?->email)) {
            throw ValidationException::withMessages(['invoice' => 'This client has no email address. Add one before sending.']);
        }

        if ($invoice->items()->count() === 0) {
            throw ValidationException::withMessages(['invoice' => 'Add at least one line item before sending.']);
        }

        $this->numbering->allocate($invoice);

        if ($invoice->status === InvoiceStatus::Draft) {
            $invoice->status = InvoiceStatus::Sent;
            $invoice->sent_at = now();
            $invoice->save();
            $this->reminders->scheduleFor($invoice);
        }

        $invoice->recordEvent(InvoiceEventType::Sent, [], $user);

        Mail::to($invoice->client->email)->queue(new InvoiceSentMail($invoice));

        return $invoice->refresh();
    }

    public function markPaidManually(Invoice $invoice, ?User $user = null): Invoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages(['invoice' => 'This invoice is already paid.']);
        }

        if ($invoice->status === InvoiceStatus::Void) {
            throw ValidationException::withMessages(['invoice' => 'A voided invoice cannot be marked as paid.']);
        }

        if (! $invoice->isPayable() && $invoice->status !== InvoiceStatus::Draft) {
            throw ValidationException::withMessages(['invoice' => 'This invoice cannot be marked as paid.']);
        }

        if ($invoice->outstandingMinor() <= 0 && $invoice->status !== InvoiceStatus::Draft) {
            throw ValidationException::withMessages(['invoice' => 'There is nothing left to mark as paid.']);
        }

        if ($invoice->status === InvoiceStatus::Draft) {
            $this->numbering->allocate($invoice);
            $invoice->sent_at = $invoice->sent_at ?? now();
        }

        $amount = max($invoice->outstandingMinor(), $invoice->total_minor > 0 ? $invoice->outstandingMinor() : 0);

        if ($invoice->total_minor === 0) {
            $invoice->status = InvoiceStatus::Paid;
            $invoice->paid_at = now();
            $invoice->amount_paid_minor = 0;
            $invoice->save();
            $this->reminders->cancelPending($invoice);
            $invoice->recordEvent(InvoiceEventType::MarkedPaid, ['amount' => 0, 'method' => 'manual'], $user);

            return $invoice->refresh();
        }

        $this->applyPayment($invoice, [
            'amount_minor' => $amount,
            'currency' => $invoice->currency,
            'fee_minor' => 0,
            'net_minor' => $amount,
            'method' => $invoice->status === InvoiceStatus::AwaitingVerification ? 'bank' : 'manual',
            'received_at' => now(),
        ], InvoiceEventType::MarkedPaid, $user);

        return $invoice->refresh();
    }

    public function reportBankPayment(Invoice $invoice): Invoice
    {
        if (! $invoice->isPayable()) {
            throw ValidationException::withMessages(['invoice' => 'This invoice cannot accept a bank payment report.']);
        }

        if ($invoice->status === InvoiceStatus::AwaitingVerification) {
            return $invoice;
        }

        $invoice->status = InvoiceStatus::AwaitingVerification;
        $invoice->save();
        $invoice->recordEvent(InvoiceEventType::BankPaymentReported, [
            'method' => 'bank',
        ]);

        return $invoice->refresh();
    }

    public function applyPayment(Invoice $invoice, array $payment, InvoiceEventType $event, ?User $user = null): Payment
    {
        return DB::transaction(function () use ($invoice, $payment, $event, $user) {
            if (! empty($payment['stripe_payment_intent_id'])) {
                $existing = $invoice->payments()
                    ->where('stripe_payment_intent_id', $payment['stripe_payment_intent_id'])
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $record = $invoice->payments()->create($payment);

            $invoice->amount_paid_minor = (int) $invoice->payments()->sum('amount_minor');

            if ($invoice->amount_paid_minor >= $invoice->total_minor) {
                $invoice->status = InvoiceStatus::Paid;
                $invoice->paid_at = $payment['received_at'] ?? now();
                $this->reminders->cancelPending($invoice);
                $this->stripe->expireSession($invoice->stripe_checkout_session_id);
                $invoice->stripe_checkout_session_id = null;
            } else {
                $invoice->status = InvoiceStatus::PartiallyPaid;
            }

            $invoice->save();
            $invoice->recordEvent($event, [
                'amount' => $payment['amount_minor'],
                'method' => $payment['method'] ?? 'stripe',
            ], $user);

            return $record;
        });
    }

    public function void(Invoice $invoice, ?User $user = null): Invoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages(['invoice' => 'Paid invoices cannot be voided. Issue a credit note or refund instead.']);
        }

        $this->stripe->expireSession($invoice->stripe_checkout_session_id);
        $invoice->status = InvoiceStatus::Void;
        $invoice->voided_at = now();
        $invoice->stripe_checkout_session_id = null;
        $invoice->save();
        $this->reminders->cancelPending($invoice);
        $invoice->recordEvent(InvoiceEventType::Voided, [], $user);

        return $invoice;
    }

    public function duplicate(Invoice $invoice, ?User $user = null): Invoice
    {
        $copy = $invoice->replicate([
            'number',
            'pay_token',
            'stripe_checkout_session_id',
            'stripe_payment_intent_id',
            'sent_at',
            'paid_at',
            'voided_at',
            'amount_paid_minor',
        ]);
        $copy->status = InvoiceStatus::Draft;
        $copy->number = null;
        $copy->pay_token = null;
        $copy->revision = 1;
        $copy->issue_date = now()->toDateString();
        $copy->due_date = now()->addDays($invoice->billingEntity->default_due_days ?? 14)->toDateString();
        $copy->created_by = $user?->id;
        $copy->save();

        foreach ($invoice->items as $item) {
            $copy->items()->create($item->only([
                'service_id', 'description', 'qty', 'unit_price_minor', 'amount_minor', 'sort_order',
            ]));
        }

        $copy->recordEvent(InvoiceEventType::Created, ['duplicated_from' => $invoice->id], $user);

        return $copy;
    }

    public function sendReminder(Invoice $invoice, bool $manual = true, ?User $user = null): void
    {
        if (! $invoice->isPayable()) {
            throw ValidationException::withMessages(['invoice' => 'Reminders are only sent for unpaid invoices.']);
        }

        $invoice->loadMissing('client');

        if (blank($invoice->client?->email)) {
            throw ValidationException::withMessages(['invoice' => 'This client has no email address. Add one before sending a reminder.']);
        }

        $reminder = Reminder::query()->create([
            'invoice_id' => $invoice->id,
            'offset_days' => 0,
            'scheduled_for' => now(),
            'sent_at' => now(),
            'is_manual' => $manual,
            'channel' => 'email',
        ]);

        Mail::to($invoice->client->email)->queue(new InvoiceReminderMail($invoice));
        $invoice->recordEvent(InvoiceEventType::ReminderSent, ['reminder_id' => $reminder->id, 'manual' => $manual], $user);
    }
}
