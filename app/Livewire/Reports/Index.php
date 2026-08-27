<?php

namespace App\Livewire\Reports;

use App\Enums\InvoiceStatus;
use App\Models\BillingEntity;
use App\Models\Client;
use App\Services\ReportService;
use App\Support\FinancialYear;
use App\Support\Money;
use App\Support\Permissions;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Reports')]
class Index extends Component
{
    #[Url]
    public int $fy = 0;

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    #[Url]
    public bool $net = false;

    #[Url]
    public string $entity_id = '';

    #[Url]
    public string $client_id = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $currency = '';

    #[Url]
    public string $search = '';

    #[Url]
    public bool $include_drafts = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permissions::REPORTS_VIEW), 403);
        if ($this->fy === 0) {
            $this->fy = FinancialYear::startFor(now())->year;
        }
    }

    public function updatedFy(): void
    {
        $this->date_from = '';
        $this->date_to = '';
    }

    public function clearFilters(): void
    {
        $this->fy = FinancialYear::startFor(now())->year;
        $this->date_from = '';
        $this->date_to = '';
        $this->entity_id = '';
        $this->client_id = '';
        $this->status = '';
        $this->currency = '';
        $this->search = '';
        $this->include_drafts = false;
        $this->net = false;
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'fy' => $this->fy,
            'date_from' => $this->date_from ?: null,
            'date_to' => $this->date_to ?: null,
            'entity_id' => $this->entity_id !== '' ? (int) $this->entity_id : null,
            'client_id' => $this->client_id !== '' ? (int) $this->client_id : null,
            'status' => $this->status !== '' ? $this->status : null,
            'currency' => $this->currency !== '' ? $this->currency : null,
            'search' => $this->search !== '' ? $this->search : null,
            'net' => $this->net,
            'include_drafts' => $this->include_drafts,
        ];
    }

    public function exportUrl(): string
    {
        return route('reports.export', array_filter([
            'fy' => $this->fy,
            'date_from' => $this->date_from ?: null,
            'date_to' => $this->date_to ?: null,
            'entity_id' => $this->entity_id ?: null,
            'client_id' => $this->client_id ?: null,
            'status' => $this->status ?: null,
            'currency' => $this->currency ?: null,
            'search' => $this->search ?: null,
            'net' => $this->net ? 1 : 0,
            'include_drafts' => $this->include_drafts ? 1 : 0,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    public function render(ReportService $reports)
    {
        $data = $reports->build($this->filters());

        return view('livewire.reports.index', [
            'data' => $data,
            'years' => $reports->availableFinancialYears(selected: $this->fy),
            'entities' => BillingEntity::query()->orderBy('name')->get(),
            'clients' => Client::query()->orderBy('company')->get(),
            'statuses' => InvoiceStatus::cases(),
            'currencies' => array_keys(config('billing.currencies')),
            'gbpInvoiced' => Money::format((int) $data['gbp_indicative_invoiced'], 'GBP'),
            'gbpReceived' => Money::format((int) $data['gbp_received'], 'GBP'),
            'exportUrl' => $this->exportUrl(),
            'resultCount' => count($data['invoice_rows']),
        ]);
    }
}
