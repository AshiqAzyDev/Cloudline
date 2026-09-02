<?php

namespace App\Livewire\Services;

use App\Models\BillingEntity;
use App\Models\Service;
use App\Support\CurrencyCatalog;
use App\Support\Money;
use App\Support\ValidationMessages;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $serviceId = null;

    public ?int $billing_entity_id = null;

    public string $name = '';

    public string $description = '';

    public string $default_rate = '0.00';

    public string $currency = 'GBP';

    public bool $is_active = true;

    public function mount(?Service $service = null): void
    {
        $this->authorize($service?->exists ? 'update' : 'create', $service?->exists ? $service : Service::class);

        if ($service?->exists) {
            $this->serviceId = $service->id;
            $this->billing_entity_id = $service->billing_entity_id;
            $this->name = $service->name;
            $this->description = $service->description ?? '';
            $this->default_rate = Money::fromMinor($service->default_rate_minor, $service->currency);
            $this->currency = $service->currency;
            $this->is_active = $service->is_active;
        }
    }

    public function save()
    {
        $service = $this->serviceId ? Service::query()->findOrFail($this->serviceId) : null;
        $this->authorize($service ? 'update' : 'create', $service ?? Service::class);

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'default_rate' => 'required|numeric|min:0|max:99999999',
            'currency' => ValidationMessages::currencyRule(),
            'billing_entity_id' => 'nullable|exists:billing_entities,id',
        ], [
            'name.required' => 'Service name is required.',
            'default_rate.required' => 'Default rate is required.',
            'default_rate.min' => 'Rate cannot be negative.',
            'default_rate.max' => 'Rate is too large.',
        ], ValidationMessages::serviceAttributes());

        $payload = [
            'billing_entity_id' => $this->billing_entity_id ?: null,
            'name' => $this->name,
            'description' => $this->description,
            'default_rate_minor' => Money::toMinor($this->default_rate, $this->currency),
            'currency' => $this->currency,
            'is_active' => $this->is_active,
        ];

        if ($service) {
            $service->update($payload);
        } else {
            Service::query()->create($payload);
        }

        session()->flash('success', 'Service saved.');

        return redirect()->route('services.index');
    }

    public function render()
    {
        return view('livewire.services.form', [
            'entities' => BillingEntity::query()->orderBy('name')->get(),
            'currencies' => CurrencyCatalog::all(),
        ])->title($this->serviceId ? 'Edit service' : 'New service');
    }
}
