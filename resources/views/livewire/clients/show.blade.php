<div class="page">
    <a href="{{ route('clients.index') }}" class="mb-3 inline-block text-[12.5px] text-muted hover:text-ink">‹ Clients</a>
    <div class="page-header">
        <div>
            <div class="page-title">{{ $client->company }}</div>
            <div class="page-subtitle">{{ $client->contact }} · {{ $client->email }}</div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-secondary">Edit</a>
        </div>
    </div>
    <div class="card card-pad mb-3 text-[12.5px] leading-5 text-label">
        {{ $client->address }}<br>
        Currency: {{ $client->default_currency }} · VAT: {{ $client->vat_treatment->label() }}
    </div>
    <div class="card overflow-x-auto">
        <div class="border-b border-line px-3 py-2 text-[10.5px] font-semibold uppercase tracking-wide text-muted">Invoices</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Entity</th>
                    <th class="right">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($client->invoices as $invoice)
                    <tr class="is-link" onclick="window.location='{{ route('invoices.show', $invoice) }}'">
                        <td class="strong num">{{ $invoice->displayNumber() }}</td>
                        <td class="muted">{{ $invoice->billingEntity->name }}</td>
                        <td class="strong num right">{{ $invoice->formattedTotal() }}</td>
                        <td><x-status-pill :status="$invoice->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="data-table-empty">No invoices for this client.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
