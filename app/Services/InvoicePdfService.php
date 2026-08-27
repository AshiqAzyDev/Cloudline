<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'client', 'billingEntity']);

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('a4')
            ->output();
    }

    public function download(Invoice $invoice)
    {
        $invoice->loadMissing(['items', 'client', 'billingEntity']);

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('a4')
            ->download($invoice->displayNumber().'.pdf');
    }
}
