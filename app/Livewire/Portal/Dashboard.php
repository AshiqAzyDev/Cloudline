<?php

namespace App\Livewire\Portal;

use App\Models\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.portal')]
#[Title('Your invoices')]
class Dashboard extends Component
{
    public function render()
    {
        $invoices = Invoice::query()
            ->with('billingEntity')
            ->where('client_id', auth()->user()->client_id)
            ->whereNot('status', 'draft')
            ->latest('issue_date')
            ->get();

        return view('livewire.portal.dashboard', compact('invoices'));
    }
}
