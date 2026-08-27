<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\ReminderRule;

class ReminderService
{
    public function scheduleFor(Invoice $invoice): void
    {
        $this->cancelPending($invoice);

        $rules = ReminderRule::query()
            ->where('billing_entity_id', $invoice->billing_entity_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($rules as $rule) {
            Reminder::query()->create([
                'invoice_id' => $invoice->id,
                'reminder_rule_id' => $rule->id,
                'offset_days' => $rule->offset_days,
                'scheduled_for' => $invoice->due_date->copy()->addDays($rule->offset_days)->startOfDay(),
                'channel' => 'email',
            ]);
        }
    }

    public function reschedule(Invoice $invoice): void
    {
        $pending = $invoice->reminders()->whereNull('sent_at')->whereNull('cancelled_at')->get();

        foreach ($pending as $reminder) {
            $reminder->update([
                'scheduled_for' => $invoice->due_date->copy()->addDays($reminder->offset_days)->startOfDay(),
            ]);
        }
    }

    public function cancelPending(Invoice $invoice): void
    {
        $invoice->reminders()
            ->whereNull('sent_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);
    }

    public function dueReminders()
    {
        return Reminder::query()
            ->with(['invoice.client', 'invoice.billingEntity', 'invoice.items'])
            ->whereNull('sent_at')
            ->whereNull('cancelled_at')
            ->where('scheduled_for', '<=', now())
            ->whereHas('invoice', fn ($query) => $query->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Overdue->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::AwaitingVerification->value,
            ]))
            ->get();
    }
}
