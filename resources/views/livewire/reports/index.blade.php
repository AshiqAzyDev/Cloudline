<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Reports</div>
            <div class="page-subtitle">{{ $data['label'] }} · {{ $resultCount }} invoice{{ $resultCount === 1 ? '' : 's' }}</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="clearFilters" class="btn btn-ghost">Reset</button>
            <a href="{{ $exportUrl }}" class="btn btn-secondary">Export filtered CSV</a>
        </div>
    </div>

    <div class="card card-pad mb-3">
        <div class="filter-bar !mb-0 grid-cols-2 md:grid-cols-4 xl:grid-cols-6">
            <div>
                <label class="field-label">Financial year</label>
                <select wire:model.live="fy" class="field">
                    @foreach ($years as $year)
                        <option value="{{ $year }}">{{ \App\Support\FinancialYear::label($year) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">From date</label>
                <input type="date" wire:model.live="date_from" class="field">
            </div>
            <div>
                <label class="field-label">To date</label>
                <input type="date" wire:model.live="date_to" class="field">
            </div>
            <div>
                <label class="field-label">Entity</label>
                <select wire:model.live="entity_id" class="field">
                    <option value="">All entities</option>
                    @foreach ($entities as $entity)
                        <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Client</label>
                <select wire:model.live="client_id" class="field">
                    <option value="">All clients</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->company }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Status</label>
                <select wire:model.live="status" class="field">
                    <option value="">All (excl. draft/void)</option>
                    @foreach ($statuses as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Currency</label>
                <select wire:model.live="currency" class="field">
                    <option value="">All currencies</option>
                    @foreach ($currencies as $code)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="field-label">Search</label>
                <input wire:model.live.debounce.300ms="search" class="field" placeholder="Invoice number or client">
            </div>
            <div class="flex items-end gap-3 pb-1">
                <label class="flex items-center gap-1.5 text-[12px] text-label">
                    <input type="checkbox" wire:model.live="net"> Net of fees
                </label>
                <label class="flex items-center gap-1.5 text-[12px] text-label">
                    <input type="checkbox" wire:model.live="include_drafts"> Include drafts
                </label>
            </div>
        </div>
        <p class="mt-2 text-[11px] text-subtle">Custom from/to dates override the financial year for filtering and export.</p>
    </div>

    <div class="mb-3 grid grid-cols-2 gap-2 md:grid-cols-4">
        <x-stat-card label="Indicative GBP invoiced" :value="$gbpInvoiced" hint="Config FX rates; settlement may differ" />
        <x-stat-card label="GBP received" :value="$gbpReceived" color="#15803D" hint="Stripe settlement & GBP payments" />
        <x-stat-card label="Invoices" :value="$resultCount" />
        <x-stat-card label="Period" :value="$data['label']" />
    </div>

    <div class="card mb-3 overflow-x-auto">
        <div class="border-b border-line px-3 py-2 text-[10.5px] font-semibold uppercase tracking-wide text-muted">Filtered invoices</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Client</th>
                    <th>Entity</th>
                    <th>Status</th>
                    <th>Issue</th>
                    <th>Due</th>
                    <th class="right">Total</th>
                    <th class="right">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['invoice_rows'] as $row)
                    <tr>
                        <td class="muted num">
                            <a href="{{ route('invoices.show', $row['id']) }}" class="table-row-link">{{ $row['number'] }}</a>
                        </td>
                        <td class="strong truncate">
                            <a href="{{ route('invoices.show', $row['id']) }}" class="table-row-link">{{ $row['client'] }}</a>
                        </td>
                        <td class="muted truncate">{{ $row['entity'] }}</td>
                        <td><x-status-pill :status="\App\Enums\InvoiceStatus::from($row['status_value'])" /></td>
                        <td class="muted">{{ $row['issue_date'] }}</td>
                        <td class="muted">{{ $row['due_date'] }}</td>
                        <td class="strong num right">{{ $row['total_fmt'] }}</td>
                        <td class="num right">{{ $row['outstanding_fmt'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="data-table-empty">No invoices match these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($resultCount > $data['invoice_per_page'])
            <div class="border-t border-line px-3 py-2">
                {{ $invoicePaginator->links() }}
            </div>
        @endif
    </div>

    <div class="card mb-3 overflow-x-auto">
        <div class="border-b border-line px-3 py-2 text-[10.5px] font-semibold uppercase tracking-wide text-muted">By currency</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Currency</th>
                    <th class="right">Invoiced</th>
                    <th class="right">Received</th>
                    <th class="right">Outstanding</th>
                    <th class="right">Overdue</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data['by_currency'] as $row)
                    <tr>
                        <td class="strong">{{ $row['currency'] }}</td>
                        <td class="num right">{{ $row['invoiced_fmt'] }}</td>
                        <td class="num right">{{ $row['received_fmt'] }}</td>
                        <td class="num right">{{ $row['outstanding_fmt'] }}</td>
                        <td class="num right text-red-700">{{ $row['overdue_fmt'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="data-table-empty">No currency totals.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mb-3 grid grid-cols-1 gap-2 md:grid-cols-3">
        <div class="card overflow-hidden">
            <div class="border-b border-line px-3 py-2 text-[10.5px] font-semibold uppercase tracking-wide text-muted">By status</div>
            <table class="data-table">
                <tbody>
                    @forelse ($data['by_status'] as $row)
                        <tr>
                            <td>{{ $row['name'] }} ({{ $row['count'] }})</td>
                            <td class="strong num right">{{ $row['currency'] === 'MIXED' ? number_format($row['amount'] / 100, 2) : \App\Support\Money::format($row['amount'], $row['currency']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="data-table-empty">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card overflow-hidden">
            <div class="border-b border-line px-3 py-2 text-[10.5px] font-semibold uppercase tracking-wide text-muted">By entity</div>
            <table class="data-table">
                <tbody>
                    @forelse ($data['by_entity'] as $row)
                        <tr>
                            <td class="truncate">{{ $row['name'] }}</td>
                            <td class="strong num right">{{ $row['currency'] === 'MIXED' ? number_format($row['invoiced'] / 100, 2) : \App\Support\Money::format($row['invoiced'], $row['currency']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="data-table-empty">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card overflow-hidden">
            <div class="border-b border-line px-3 py-2 text-[10.5px] font-semibold uppercase tracking-wide text-muted">By client</div>
            <table class="data-table">
                <tbody>
                    @forelse ($data['by_client'] as $row)
                        <tr>
                            <td class="truncate">{{ $row['name'] }}</td>
                            <td class="strong num right">{{ $row['currency'] === 'MIXED' ? number_format($row['invoiced'] / 100, 2) : \App\Support\Money::format($row['invoiced'], $row['currency']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="data-table-empty">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b border-line px-3 py-2 text-[10.5px] font-semibold uppercase tracking-wide text-muted">By service</div>
            <table class="data-table">
                <tbody>
                    @forelse ($data['by_service'] as $row)
                        <tr>
                            <td class="truncate">{{ $row['name'] }} <span class="subtle">({{ $row['currency'] }})</span></td>
                            <td class="strong num right">{{ \App\Support\Money::format($row['amount'], $row['currency']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="data-table-empty">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card overflow-hidden">
            <div class="border-b border-line px-3 py-2 text-[10.5px] font-semibold uppercase tracking-wide text-muted">Monthly (GBP)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="right">Invoiced</th>
                        <th class="right">Received</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['monthly'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="muted num right">{{ \App\Support\Money::format($row['invoiced_gbp'], 'GBP') }}</td>
                            <td class="strong num right">{{ \App\Support\Money::format($row['received_gbp'], 'GBP') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
