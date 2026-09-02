<?php

namespace App\Livewire;

use App\Enums\InvoiceStatus;
use App\Models\BillingEntity;
use App\Models\Invoice;
use App\Support\FinancialYear;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $this->authorize('viewAny', Invoice::class);

        $fyStart = FinancialYear::startFor(now());
        [$fyFrom, $fyTo] = FinancialYear::rangeForStartYear($fyStart->year);

        $overdueStats = $this->amountsByCurrency(
            Invoice::query()->where('status', InvoiceStatus::Overdue),
            'total_minor - amount_paid_minor',
        );

        $unpaidStats = $this->amountsByCurrency(
            Invoice::query()->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Overdue->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::AwaitingVerification->value,
            ]),
            'total_minor - amount_paid_minor',
        );

        $unsentStats = $this->amountsByCurrency(
            Invoice::query()->where('status', InvoiceStatus::Draft),
            'total_minor',
        );

        $monthSalesStats = $this->amountsByCurrency(
            Invoice::query()
                ->where('status', InvoiceStatus::Paid)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year),
            'total_minor',
        );

        $fyCount = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Void->value])
            ->whereBetween('issue_date', [$fyFrom->toDateString(), $fyTo->toDateString()])
            ->count();

        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', issue_date)",
            'pgsql' => "to_char(issue_date, 'YYYY-MM')",
            default => "DATE_FORMAT(issue_date, '%Y-%m')",
        };

        $monthlyStats = DB::table('invoices')
            ->selectRaw("{$monthExpression} as month_key, SUM(CASE WHEN currency = 'GBP' THEN total_minor ELSE COALESCE(total_gbp_minor, 0) END) as amount, COUNT(*) as invoice_count")
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Void->value])
            ->whereBetween('issue_date', [$fyFrom->toDateString(), $fyTo->toDateString()])
            ->groupBy('month_key')
            ->get()
            ->keyBy('month_key');

        $bars = [];
        $cursor = $fyFrom->startOfMonth();
        while ($cursor->lte($fyTo)) {
            $key = $cursor->format('Y-m');
            $stat = $monthlyStats->get($key);
            $amount = (int) ($stat->amount ?? 0);
            $count = (int) ($stat->invoice_count ?? 0);
            $bars[] = [
                'label' => $cursor->format('M'),
                'month' => $cursor->format('F Y'),
                'amount' => $amount,
                'count' => $count,
                'formatted' => Money::format($amount, 'GBP'),
            ];
            $cursor = $cursor->addMonth();
        }

        $fyGbpTotal = (int) collect($bars)->sum('amount');
        $monthsElapsed = max(1, collect($bars)->filter(fn ($row) => $row['amount'] > 0)->count() ?: 1);
        $monthlyAverage = (int) round($fyGbpTotal / $monthsElapsed);
        $maxBar = max(1, collect($bars)->max('amount') ?: 1);

        $recent = Invoice::query()
            ->with(['client', 'billingEntity', 'items'])
            ->latest('issue_date')
            ->limit(15)
            ->get();

        $entityNames = BillingEntity::query()->where('is_active', true)->orderBy('name')->pluck('name');

        return view('livewire.dashboard', [
            'overdueCount' => array_sum(array_column($overdueStats, 'count')),
            'overdueAmount' => $this->formatStats($overdueStats),
            'unpaidCount' => array_sum(array_column($unpaidStats, 'count')),
            'unpaidAmount' => $this->formatStats($unpaidStats),
            'unsentCount' => array_sum(array_column($unsentStats, 'count')),
            'unsentAmount' => $this->formatStats($unsentStats),
            'monthLabel' => now()->format('F').' sales',
            'monthSales' => $this->formatStats($monthSalesStats),
            'fyLabel' => FinancialYear::label($fyStart->year).' sales',
            'fyCount' => $fyCount,
            'fySales' => Money::format($fyGbpTotal, 'GBP'),
            'fyAverage' => Money::format($monthlyAverage, 'GBP'),
            'fyAverageMinor' => $monthlyAverage,
            'monthlyBars' => $bars,
            'maxBar' => $maxBar,
            'invoices' => $recent,
            'entitySubtitle' => $entityNames->implode(' · ') ?: 'Billing',
        ]);
    }

    /**
     * @return list<array{currency: string, amount: int, count: int}>
     */
    private function amountsByCurrency($query, string $amountExpression): array
    {
        return $query
            ->selectRaw('currency, SUM('.$amountExpression.') as amount, COUNT(*) as count')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get()
            ->map(fn ($row) => [
                'currency' => $row->currency,
                'amount' => (int) $row->amount,
                'count' => (int) $row->count,
            ])
            ->all();
    }

    /**
     * @param  list<array{currency: string, amount: int, count: int}>  $stats
     */
    private function formatStats(array $stats): string
    {
        if ($stats === []) {
            return Money::format(0, 'GBP');
        }

        return collect($stats)
            ->map(fn (array $row) => Money::format($row['amount'], $row['currency']))
            ->implode(' · ');
    }
}
