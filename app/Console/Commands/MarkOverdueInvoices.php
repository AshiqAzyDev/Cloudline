<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark sent invoices as overdue once their due date has passed';

    public function handle(): int
    {
        $count = Invoice::query()
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::PartiallyPaid->value])
            ->whereDate('due_date', '<', now()->toDateString())
            ->update(['status' => InvoiceStatus::Overdue->value]);

        $this->info("Marked {$count} invoice(s) overdue.");

        return self::SUCCESS;
    }
}
