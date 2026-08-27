<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceStatus;
use App\Enums\VatTreatment;
use App\Models\BillingEntity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\InvoiceService;
use App\Support\Money;
use App\Support\ValidationMessages;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?int $invoiceId = null;

    public ?int $billing_entity_id = null;

    public ?int $client_id = null;

    public string $currency = 'GBP';

    public string $issue_date = '';

    public string $due_date = '';

    public bool $vat_enabled = true;

    public string $vat_rate = '20';

    public string $vat_treatment = 'standard';

    public string $notes = '';

    public string $terms = '';

    public array $items = [];

    public bool $showQuickClient = false;

    public string $quick_company = '';

    public string $quick_email = '';

    public string $quick_contact = '';

    public bool $notesOnly = false;

    public function mount(?Invoice $invoice = null): void
    {
        $this->authorize($invoice?->exists ? 'update' : 'create', $invoice?->exists ? $invoice : Invoice::class);

        $this->issue_date = now()->toDateString();
        $this->due_date = now()->addDays((int) config('billing.default_due_days', 14))->toDateString();
        $entity = BillingEntity::query()->where('is_active', true)->orderBy('id')->first();
        $this->billing_entity_id = $entity?->id;
        $this->terms = $entity?->terms ?? '';
        if ($entity) {
            $this->applyEntityVatDefaults($entity);
            $this->due_date = Carbon::parse($this->issue_date)->addDays($entity->default_due_days)->toDateString();
        } else {
            $this->vat_rate = (string) config('billing.default_vat_rate');
        }
        $this->addCustomItem();

        if ($invoice?->exists) {
            if ($invoice->status === InvoiceStatus::Paid) {
                $this->notesOnly = true;
            } elseif (! $invoice->isEditable()) {
                abort(403, 'This invoice cannot be edited.');
            }

            $this->invoiceId = $invoice->id;
            $this->billing_entity_id = $invoice->billing_entity_id;
            $this->client_id = $invoice->client_id;
            $this->currency = $invoice->currency;
            $this->issue_date = $invoice->issue_date->toDateString();
            $this->due_date = $invoice->due_date->toDateString();
            $this->vat_enabled = $invoice->vat_enabled;
            $this->vat_rate = (string) $invoice->vat_rate;
            $this->vat_treatment = $invoice->vat_treatment->value;
            $this->notes = $invoice->notes ?? '';
            $this->terms = $invoice->terms ?? '';
            $this->items = $invoice->items->map(fn ($item) => [
                'service_id' => $item->service_id,
                'description' => $item->description,
                'qty' => (string) $item->qty,
                'unit_price' => Money::fromMinor($item->unit_price_minor, $invoice->currency),
            ])->all() ?: [[
                'service_id' => null,
                'description' => '',
                'qty' => '1',
                'unit_price' => '0.00',
            ]];
        }
    }

    public function updatedClientId($value): void
    {
        if ($this->notesOnly) {
            return;
        }

        $client = Client::query()->find($value);
        if (! $client) {
            return;
        }
        $this->currency = $client->default_currency;
        $this->vat_treatment = $client->vat_treatment->value;
        if (! $client->vat_treatment->appliesVat()) {
            $this->vat_enabled = false;
        }
    }

    public function updatedBillingEntityId($value): void
    {
        if ($this->notesOnly) {
            return;
        }

        $entity = BillingEntity::query()->find($value);
        if (! $entity) {
            return;
        }

        $this->applyEntityVatDefaults($entity);

        if (! $this->terms) {
            $this->terms = $entity->terms ?? '';
        }
        if ($this->issue_date) {
            $this->due_date = Carbon::parse($this->issue_date)->addDays($entity->default_due_days)->toDateString();
        }
    }

    public function setDuePreset(int $days): void
    {
        if ($this->notesOnly || ! $this->issue_date) {
            return;
        }

        $this->due_date = Carbon::parse($this->issue_date)->addDays($days)->toDateString();
    }

    protected function applyEntityVatDefaults(BillingEntity $entity): void
    {
        if ($entity->vat_registered) {
            $this->vat_enabled = true;
            $this->vat_rate = rtrim(rtrim(number_format((float) ($entity->default_vat_rate ?: config('billing.default_vat_rate', 20)), 2, '.', ''), '0'), '.') ?: '0';
            $this->vat_treatment = VatTreatment::Standard->value;
        } else {
            $this->vat_enabled = false;
            $this->vat_rate = '0';
            $this->vat_treatment = VatTreatment::OutOfScope->value;
        }
    }

    public function updatedVatTreatment($value): void
    {
        $treatment = VatTreatment::tryFrom($value);
        if ($treatment && ! $treatment->appliesVat()) {
            $this->vat_enabled = false;
        }
    }

    public function addPreset(int $serviceId): void
    {
        if ($this->notesOnly) {
            return;
        }

        $service = Service::query()->find($serviceId);
        if (! $service) {
            return;
        }

        if ($service->currency !== $this->currency) {
            $this->addError('items', "“{$service->name}” is priced in {$service->currency}. Switch the invoice currency or enter a custom rate.");

            return;
        }

        $this->items[] = [
            'service_id' => $service->id,
            'description' => $service->description ?: $service->name,
            'qty' => '1',
            'unit_price' => Money::fromMinor($service->default_rate_minor, $service->currency),
        ];
    }

    public function addCustomItem(): void
    {
        if ($this->notesOnly) {
            return;
        }

        $this->items[] = [
            'service_id' => null,
            'description' => '',
            'qty' => '1',
            'unit_price' => '0.00',
        ];
    }

    public function applyService($index, $serviceId): void
    {
        if ($this->notesOnly) {
            return;
        }

        if ($serviceId === 'custom' || $serviceId === '') {
            $this->items[$index]['service_id'] = null;

            return;
        }

        $service = Service::query()->find($serviceId);
        if (! $service) {
            return;
        }

        if ($service->currency !== $this->currency) {
            $this->addError("items.{$index}.description", "This service is priced in {$service->currency}. Enter a custom rate in {$this->currency} instead.");
            $this->items[$index]['service_id'] = null;

            return;
        }

        $this->items[$index]['service_id'] = $service->id;
        $this->items[$index]['description'] = $service->description ?: $service->name;
        $this->items[$index]['unit_price'] = Money::fromMinor($service->default_rate_minor, $service->currency);
    }

    public function removeItem(int $index): void
    {
        if ($this->notesOnly) {
            return;
        }

        if (count($this->items) <= 1) {
            $this->addError('items', 'An invoice needs at least one line item.');

            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function createQuickClient(): void
    {
        $this->authorize('create', Client::class);

        $this->validate([
            'quick_company' => 'required|string|max:255',
            'quick_email' => 'required|email|max:255',
            'quick_contact' => 'nullable|string|max:255',
        ], ValidationMessages::invoiceMessages(), ValidationMessages::invoiceAttributes());

        $client = Client::query()->create([
            'company' => $this->quick_company,
            'email' => $this->quick_email,
            'contact' => $this->quick_contact,
            'default_currency' => $this->currency,
            'vat_treatment' => $this->vat_treatment,
        ]);

        $this->client_id = $client->id;
        $this->showQuickClient = false;
        $this->quick_company = $this->quick_email = $this->quick_contact = '';
        $this->resetErrorBag('quick_company', 'quick_email', 'quick_contact');
    }

    public function saveDraft(): mixed
    {
        return $this->persist(send: false);
    }

    public function saveAndEmail(): mixed
    {
        return $this->persist(send: true);
    }

    protected function persist(bool $send = false)
    {
        $invoice = $this->invoiceId ? Invoice::query()->findOrFail($this->invoiceId) : null;
        $this->authorize($invoice ? 'update' : 'create', $invoice ?? Invoice::class);

        if ($this->notesOnly && $invoice) {
            $invoice->update(['notes' => $this->notes]);
            session()->flash('success', 'Invoice notes updated.');

            return redirect()->route('invoices.show', $invoice);
        }

        if (! $this->billing_entity_id) {
            throw ValidationException::withMessages([
                'billing_entity_id' => 'Please create a billing entity in Settings before raising an invoice.',
            ]);
        }

        $this->validate([
            'billing_entity_id' => 'required|exists:billing_entities,id',
            'client_id' => 'required|exists:clients,id',
            'currency' => ValidationMessages::currencyRule(),
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'vat_treatment' => ['required', Rule::enum(VatTreatment::class)],
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], ValidationMessages::invoiceMessages(), ValidationMessages::invoiceAttributes());

        if ($this->vat_enabled && ! is_numeric($this->vat_rate)) {
            throw ValidationException::withMessages([
                'vat_rate' => 'Enter a valid VAT rate.',
            ]);
        }

        $client = Client::query()->findOrFail($this->client_id);
        if ($send && blank($client->email)) {
            throw ValidationException::withMessages([
                'client_id' => 'This client has no email address. Add one before sending the invoice.',
            ]);
        }

        try {
            $invoices = app(InvoiceService::class);
            $invoice = $invoices->save([
                'billing_entity_id' => $this->billing_entity_id,
                'client_id' => $this->client_id,
                'currency' => $this->currency,
                'issue_date' => $this->issue_date,
                'due_date' => $this->due_date,
                'vat_enabled' => $this->vat_enabled,
                'vat_rate' => (float) $this->vat_rate,
                'vat_treatment' => $this->vat_treatment,
                'notes' => $this->notes,
                'terms' => $this->terms,
            ], $this->items, $invoice, auth()->user());

            if ($send) {
                $invoices->send($invoice, auth()->user());
                session()->flash('success', 'Invoice saved and emailed to the client.');
            } else {
                session()->flash('success', 'Invoice saved as draft.');
            }
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()->route('invoices.show', $invoice);
    }

    public function render(InvoiceService $invoices)
    {
        $treatment = VatTreatment::tryFrom($this->vat_treatment) ?? VatTreatment::Standard;
        $totals = $invoices->calculateTotals(
            $this->items,
            $this->currency,
            $this->vat_enabled,
            (float) $this->vat_rate,
            $treatment,
        );

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('livewire.invoices.form', [
            'entities' => BillingEntity::query()->where('is_active', true)->orderBy('name')->get(),
            'clients' => Client::query()->orderBy('company')->get(),
            'services' => $services,
            'matchingServices' => $services->where('currency', $this->currency)->values(),
            'currencies' => config('billing.currencies'),
            'treatments' => VatTreatment::cases(),
            'totals' => $totals,
            'title' => $this->invoiceId ? 'Edit invoice' : 'New invoice',
        ])->title($this->invoiceId ? 'Edit invoice' : 'New invoice');
    }
}
