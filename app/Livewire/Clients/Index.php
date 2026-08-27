<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Clients')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function render()
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::query()
            ->withCount('invoices')
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('company', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('contact', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('company')
            ->paginate(20);

        return view('livewire.clients.index', compact('clients'));
    }
}
