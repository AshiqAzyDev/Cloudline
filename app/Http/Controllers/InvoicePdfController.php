<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoicePdfService;

class InvoicePdfController extends Controller
{
    public function staff(Invoice $invoice, InvoicePdfService $pdf)
    {
        $this->authorize('view', $invoice);

        return $pdf->download($invoice);
    }

    public function portal(Invoice $invoice, InvoicePdfService $pdf)
    {
        $this->authorize('view', $invoice);

        return $pdf->download($invoice);
    }
}
