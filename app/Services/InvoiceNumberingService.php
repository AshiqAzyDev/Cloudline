<?php

namespace App\Services;

use App\Models\BillingEntity;
use App\Models\Invoice;
use App\Support\FinancialYear;
use Illuminate\Support\Facades\DB;

class InvoiceNumberingService
{
    public function allocate(Invoice $invoice): string
    {
        if ($invoice->number) {
            return $invoice->number;
        }

        return DB::transaction(function () use ($invoice) {
            /** @var BillingEntity $entity */
            $entity = BillingEntity::query()
                ->whereKey($invoice->billing_entity_id)
                ->lockForUpdate()
                ->firstOrFail();

            $year = FinancialYear::startFor($invoice->issue_date ?? now())->year;
            $sequence = $entity->next_invoice_number;
            $number = sprintf('%s-%d-%04d', $entity->invoice_prefix, $year, $sequence);

            $entity->numbering_year = $year;
            $entity->next_invoice_number = $sequence + 1;
            $entity->save();

            $invoice->number = $number;
            $invoice->save();

            return $number;
        });
    }
}
