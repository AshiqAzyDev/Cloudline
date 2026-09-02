<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Client')]
class Show extends Component
{
    public Client $client;

    public function mount(Client $client): void
    {
        $this->authorize('view', $client);
        $this->client = $client->load(['invoices.billingEntity', 'users']);
    }

    public function render()
    {
        return view('livewire.clients.show');
    }
}
