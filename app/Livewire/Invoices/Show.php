<?php

namespace App\Livewire\Invoices;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Invoice')]
class Show extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);
        $this->invoice = $invoice->load(['items', 'client', 'billingEntity', 'events.user', 'reminders', 'payments']);
    }

    public function send(InvoiceService $invoices)
    {
        $this->authorize('send', $this->invoice);

        try {
            $invoices->send($this->invoice, auth()->user());
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage() ?: collect($e->errors())->flatten()->first());

            return redirect()->route('invoices.show', $this->invoice);
        }

        session()->flash('success', 'Invoice emailed to the client.');

        return redirect()->route('invoices.show', $this->invoice);
    }

    public function remind(InvoiceService $invoices)
    {
        $this->authorize('send', $this->invoice);

        try {
            $invoices->sendReminder($this->invoice, true, auth()->user());
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first() ?: $e->getMessage());

            return redirect()->route('invoices.show', $this->invoice);
        }

        session()->flash('success', 'Reminder sent.');

        return redirect()->route('invoices.show', $this->invoice);
    }

    public function markPaid(InvoiceService $invoices)
    {
        $this->authorize('update', $this->invoice);

        try {
            $invoices->markPaidManually($this->invoice, auth()->user());
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first() ?: $e->getMessage());

            return redirect()->route('invoices.show', $this->invoice);
        }

        session()->flash('success', 'Invoice marked as paid.');

        return redirect()->route('invoices.show', $this->invoice);
    }

    public function void(InvoiceService $invoices)
    {
        $this->authorize('void', $this->invoice);

        try {
            $invoices->void($this->invoice, auth()->user());
        } catch (ValidationException $e) {
            session()->flash('error', collect($e->errors())->flatten()->first() ?: $e->getMessage());

            return redirect()->route('invoices.show', $this->invoice);
        }

        session()->flash('success', 'Invoice voided.');

        return redirect()->route('invoices.show', $this->invoice);
    }

    public function duplicate(InvoiceService $invoices)
    {
        $this->authorize('create', Invoice::class);
        $copy = $invoices->duplicate($this->invoice->load('items', 'billingEntity'), auth()->user());

        return redirect()->route('invoices.edit', $copy);
    }

    public function render()
    {
        return view('livewire.invoices.show', [
            'payUrl' => route('pay.show', $this->invoice->pay_token),
        ]);
    }
}
