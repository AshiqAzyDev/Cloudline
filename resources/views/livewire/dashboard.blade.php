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
        <x-stat-card label="Overdue ({{ $overdueCount }})" :value="$overdueAmount" color="#B91C1C" />
        <x-stat-card label="Unpaid ({{ $unpaidCount }})" :value="$unpaidAmount" />
        <x-stat-card label="Unsent ({{ $unsentCount }})" :value="$unsentAmount" />
        <x-stat-card :label="$monthLabel" :value="$monthSales" />
    </div>

    <div class="card mb-3 px-3 py-2.5">
        <div class="mb-1 flex items-center justify-between gap-3">
            <div class="text-[10.5px] font-semibold uppercase tracking-wide text-muted">{{ $fyLabel }} ({{ $fyCount }})</div>
            <a href="{{ route('reports.index') }}" class="text-[12px] font-semibold text-accent">See more</a>
        </div>
        <div class="text-lg font-bold tabular-nums">{{ $fySales }}</div>
        <div class="mt-0.5 text-[11px] text-subtle">{{ $fyAverage }} monthly average · indicative GBP for non-GBP invoices</div>
        <div class="chart-bars mt-3">
            @foreach ($monthlyBars as $bar)
                @php
                    $pct = $bar['amount'] > 0 && $maxBar > 0 ? max(8, ($bar['amount'] / $maxBar) * 100) : 3;
                    $avgPct = $fyAverageMinor > 0 && $maxBar > 0 ? min(100, ($fyAverageMinor / $maxBar) * 100) : null;
                    $vsAverage = $bar['amount'] > 0 && $fyAverageMinor > 0
                        ? round((($bar['amount'] - $fyAverageMinor) / $fyAverageMinor) * 100)
                        : null;
                @endphp
                <div class="chart-bar-col" tabindex="0">
                    <div class="chart-tooltip" role="tooltip">
                        <div class="chart-tooltip-title">{{ $bar['month'] }}</div>
                        <div class="chart-tooltip-amount">{{ $bar['formatted'] }}</div>
                        <div class="chart-tooltip-meta">
                            {{ $bar['count'] }} {{ \Illuminate\Support\Str::plural('invoice', $bar['count']) }}
                            @if ($vsAverage !== null)
                                · {{ $vsAverage >= 0 ? '+' : '' }}{{ $vsAverage }}% vs avg
                            @endif
                        </div>
                    </div>
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
                    <tr>
                        <td class="muted num">
                            <a href="{{ route('invoices.show', $invoice) }}" class="table-row-link">{{ $invoice->displayNumber() }}</a>
                        </td>
                        <td class="strong truncate">
                            <a href="{{ route('invoices.show', $invoice) }}" class="table-row-link">{{ $invoice->client->company }}</a>
                        </td>
                        <td class="strong num right">{{ $invoice->formattedTotal() }}</td>
                        <td class="muted">{{ $invoice->due_date->format('M j') }}</td>
                        <td><x-status-pill :status="$invoice->status" /></td>
                        <td class="muted">→</td>
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
