<div class="page">
    <div class="page-header">
        <div class="page-title">All invoices</div>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">+ New invoice</a>
    </div>

    <div class="filter-bar grid-cols-2 md:grid-cols-6">
        <input wire:model.live.debounce.300ms="search" placeholder="Search number or client" class="field md:col-span-2">
        <select wire:model.live="status" class="field">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="currency" class="field">
            <option value="">All currencies</option>
            @foreach ($currencies as $code)
                <option value="{{ $code }}">{{ $code }}</option>
            @endforeach
        </select>
        <select wire:model.live="client_id" class="field">
            <option value="">All clients</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}">{{ $client->company }}</option>
            @endforeach
        </select>
        <select wire:model.live="entity_id" class="field">
            <option value="">All entities</option>
            @foreach ($entities as $entity)
                <option value="{{ $entity->id }}">{{ $entity->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <colgroup>
                <col style="width: 7.5rem">
                <col>
                <col style="width: 4rem">
                <col style="width: 7.5rem">
                <col style="width: 4.5rem">
                <col style="width: 6rem">
            </colgroup>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Client</th>
                    <th>Entity</th>
                    <th class="right">Amount</th>
                    <th>Due</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="muted num">
                            <a href="{{ route('invoices.show', $invoice) }}" class="table-row-link">{{ $invoice->displayNumber() }}</a>
                        </td>
                        <td class="strong truncate">
                            <a href="{{ route('invoices.show', $invoice) }}" class="table-row-link">{{ $invoice->client->company }}</a>
                        </td>
                        <td class="muted">{{ $invoice->billingEntity->invoice_prefix }}</td>
                        <td class="strong num right">{{ $invoice->formattedTotal() }}</td>
                        <td class="muted">{{ $invoice->due_date->format('M j') }}</td>
                        <td><x-status-pill :status="$invoice->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="data-table-empty">No invoices match those filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $invoices->links() }}</div>
</div>
