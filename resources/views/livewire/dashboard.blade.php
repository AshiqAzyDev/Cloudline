<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">Invoices</div>
            <div class="page-subtitle">{{ $entitySubtitle }}</div>
        </div>
        @can('create', App\Models\Invoice::class)
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">+ New invoice</a>
        @endcan
    </div>

    <div class="mb-3 grid grid-cols-2 gap-2 lg:grid-cols-4">
        <div class="card px-3 py-2.5">
            <div class="text-[10.5px] font-semibold uppercase tracking-wide text-red-700">Overdue ({{ $overdueCount }})</div>
            <div class="mt-0.5 text-lg font-bold tabular-nums">{{ $overdueAmount }}</div>
        </div>
        <div class="card px-3 py-2.5">
            <div class="text-[10.5px] font-semibold uppercase tracking-wide text-muted">Unpaid ({{ $unpaidCount }})</div>
            <div class="mt-0.5 text-lg font-bold tabular-nums">{{ $unpaidAmount }}</div>
        </div>
        <div class="card px-3 py-2.5">
            <div class="text-[10.5px] font-semibold uppercase tracking-wide text-muted">Unsent ({{ $unsentCount }})</div>
            <div class="mt-0.5 text-lg font-bold tabular-nums">{{ $unsentAmount }}</div>
        </div>
        <div class="card px-3 py-2.5">
            <div class="text-[10.5px] font-semibold uppercase tracking-wide text-muted">{{ $monthLabel }}</div>
            <div class="mt-0.5 text-lg font-bold tabular-nums">{{ $monthSales }}</div>
        </div>
    </div>

    <div class="card mb-3 px-3 py-2.5">
        <div class="mb-1 flex items-center justify-between gap-3">
            <div class="text-[10.5px] font-semibold uppercase tracking-wide text-muted">{{ $fyLabel }} ({{ $fyCount }})</div>
            <a href="{{ route('reports.index') }}" class="text-[12px] font-semibold text-accent">See more</a>
        </div>
        <div class="text-lg font-bold tabular-nums">{{ $fySales }}</div>
        <div class="mt-0.5 text-[11px] text-muted">{{ $fyAverage }} monthly average</div>
        <div class="chart-bars mt-3">
            @foreach ($monthlyBars as $bar)
                @php
                    $pct = $bar['amount'] > 0 && $maxBar > 0 ? max(8, ($bar['amount'] / $maxBar) * 100) : 3;
                    $avgPct = $fyAverageMinor > 0 && $maxBar > 0 ? min(100, ($fyAverageMinor / $maxBar) * 100) : null;
                @endphp
                <div class="chart-bar-col">
                    <div class="chart-bar-track">
                        @if ($avgPct !== null)
                            <div class="chart-bar-avg" style="bottom: {{ $avgPct }}%"></div>
                        @endif
                        <div class="chart-bar" style="height: {{ $pct }}%"></div>
                    </div>
                    <div class="text-[9px] text-subtle">{{ $bar['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <colgroup>
                <col style="width: 7.5rem">
                <col>
                <col style="width: 7.5rem">
                <col style="width: 4.5rem">
                <col style="width: 6rem">
                <col style="width: 1.5rem">
            </colgroup>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Client</th>
                    <th class="right">Amount</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr class="is-link" onclick="window.location='{{ route('invoices.show', $invoice) }}'">
                        <td class="muted num">{{ $invoice->displayNumber() }}</td>
                        <td>
                            <div class="strong truncate">{{ $invoice->client->company }}</div>
                            <div class="subtle truncate">{{ $invoice->items->first()?->description ?? $invoice->billingEntity->name }}</div>
                        </td>
                        <td class="strong num right">{{ $invoice->formattedTotal() }}</td>
                        <td class="muted">{{ $invoice->due_date->format('M j') }}</td>
                        <td><x-status-pill :status="$invoice->status" /></td>
                        <td class="subtle">›</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="data-table-empty">No invoices yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
