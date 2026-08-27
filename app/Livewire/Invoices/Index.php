<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\BillingEntity;
use App\Models\Client;
use App\Models\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Invoices')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $client_id = '';

    #[Url]
    public string $entity_id = '';

    #[Url]
    public string $currency = '';

    public function render()
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['client', 'billingEntity', 'items'])
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('client', fn ($c) => $c->where('company', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->client_id, fn ($q) => $q->where('client_id', $this->client_id))
            ->when($this->entity_id, fn ($q) => $q->where('billing_entity_id', $this->entity_id))
            ->when($this->currency, fn ($q) => $q->where('currency', $this->currency))
            ->latest('issue_date')
            ->paginate(20);

        return view('livewire.invoices.index', [
            'invoices' => $invoices,
            'clients' => Client::query()->orderBy('company')->get(),
            'entities' => BillingEntity::query()->orderBy('name')->get(),
            'statuses' => InvoiceStatus::cases(),
            'currencies' => array_keys(config('billing.currencies')),
        ]);
    }
}
