<?php

namespace App\Livewire\Portal;

use App\Models\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal')]
#[Title('Invoice')]
class InvoiceShow extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);
        $this->invoice = $invoice->load(['items', 'billingEntity', 'client']);
    }

    public function render()
    {
        return view('livewire.portal.invoice-show');
    }
}
