<?php

namespace App\Console\Commands;

use App\Enums\InvoiceEventType;
use App\Mail\InvoiceReminderMail;
use App\Services\ReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-reminders';

    protected $description = 'Send scheduled invoice payment reminders';

    public function handle(ReminderService $reminders): int
    {
        $due = $reminders->dueReminders();
        $count = 0;

        foreach ($due as $reminder) {
            $invoice = $reminder->invoice;

            if (! $invoice->isPayable()) {
                $reminder->update(['cancelled_at' => now()]);

                continue;
            }

            Mail::to($invoice->client->email)->queue(new InvoiceReminderMail($invoice));
            $reminder->update(['sent_at' => now()]);
            $invoice->recordEvent(InvoiceEventType::ReminderSent, [
                'reminder_id' => $reminder->id,
                'manual' => false,
            ]);
            $count++;
        }

        $this->info("Sent {$count} reminder(s).");

        return self::SUCCESS;
    }
}
