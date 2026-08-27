<?php

namespace App\Livewire\Clients;

use App\Enums\VatTreatment;
use App\Models\Client;
use App\Support\ValidationMessages;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $clientId = null;

    public string $company = '';

    public string $contact = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $country = 'GB';

    public string $vat_number = '';

    public string $default_currency = 'GBP';

    public string $vat_treatment = 'standard';

    public string $notes = '';

    public function mount(?Client $client = null): void
    {
        $this->authorize($client?->exists ? 'update' : 'create', $client?->exists ? $client : Client::class);

        if ($client?->exists) {
            $this->clientId = $client->id;
            $this->fill($client->only([
                'company', 'contact', 'email', 'phone', 'address', 'country', 'vat_number', 'default_currency', 'notes',
            ]));
            $this->country = $client->country ?: 'GB';
            $this->vat_treatment = $client->vat_treatment->value;
        }
    }

    public function save()
    {
        $client = $this->clientId ? Client::query()->findOrFail($this->clientId) : null;
        $this->authorize($client ? 'update' : 'create', $client ?? Client::class);

        $data = $this->validate([
            'company' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($this->clientId)->whereNull('deleted_at'),
            ],
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:2000',
            'country' => 'nullable|string|size:2',
            'vat_number' => 'nullable|string|max:50',
            'default_currency' => ValidationMessages::currencyRule(),
            'vat_treatment' => ['required', Rule::enum(VatTreatment::class)],
            'notes' => 'nullable|string|max:5000',
        ], [
            'company.required' => 'Company name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'Another client already uses this email.',
            'country.size' => 'Country must be a 2-letter ISO code (e.g. GB).',
        ], ValidationMessages::clientAttributes());

        $data['country'] = $data['country'] ?: null;

        if ($client) {
            $client->update($data);
        } else {
            $client = Client::query()->create($data);
        }

        session()->flash('success', 'Client saved.');

        return redirect()->route('clients.show', $client);
    }

    public function render()
    {
        return view('livewire.clients.form', [
            'currencies' => config('billing.currencies'),
            'treatments' => VatTreatment::cases(),
        ])->title($this->clientId ? 'Edit client' : 'New client');
    }
}
