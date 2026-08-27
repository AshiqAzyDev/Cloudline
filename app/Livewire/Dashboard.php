<?php

namespace App\Livewire;

use App\Enums\InvoiceStatus;
use App\Models\BillingEntity;
use App\Models\Invoice;
use App\Support\FinancialYear;
use App\Support\Money;
use Illuminate\Support\Collection;
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

        $overdue = Invoice::query()
            ->where('status', InvoiceStatus::Overdue)
            ->get();

        $unpaid = Invoice::query()
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Overdue->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::AwaitingVerification->value,
            ])
            ->get();

        $unsent = Invoice::query()
            ->where('status', InvoiceStatus::Draft)
            ->get();

        $paidThisMonth = Invoice::query()
            ->where('status', InvoiceStatus::Paid)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->get();

        $fyInvoices = Invoice::query()
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Void->value])
            ->whereBetween('issue_date', [$fyFrom->toDateString(), $fyTo->toDateString()])
            ->get();

        $monthlyBars = [];
        $cursor = $fyFrom->startOfMonth();
        while ($cursor->lte($fyTo)) {
            $monthTotal = $fyInvoices
                ->filter(fn (Invoice $invoice) => $invoice->issue_date->isSameMonth($cursor))
                ->sum(fn (Invoice $invoice) => $invoice->currency === 'GBP'
                    ? $invoice->total_minor
                    : ($invoice->total_gbp_minor ?? 0));
            $monthlyBars[] = [
                'label' => $cursor->format('M'),
                'amount' => (int) $monthTotal,
            ];
            $cursor = $cursor->addMonth();
        }

        $fyGbpTotal = (int) collect($monthlyBars)->sum('amount');
        $monthsElapsed = max(1, collect($monthlyBars)->filter(fn ($row) => $row['amount'] > 0)->count() ?: 1);
        $monthlyAverage = (int) round($fyGbpTotal / $monthsElapsed);
        $maxBar = max(1, collect($monthlyBars)->max('amount') ?: 1);

        $recent = Invoice::query()
            ->with(['client', 'billingEntity', 'items'])
            ->latest('issue_date')
            ->limit(15)
            ->get();

        $entityNames = BillingEntity::query()->where('is_active', true)->orderBy('name')->pluck('name');

        return view('livewire.dashboard', [
            'overdueCount' => $overdue->count(),
            'overdueAmount' => $this->formatAmounts($overdue, fn (Invoice $i) => $i->outstandingMinor()),
            'unpaidCount' => $unpaid->count(),
            'unpaidAmount' => $this->formatAmounts($unpaid, fn (Invoice $i) => $i->outstandingMinor()),
            'unsentCount' => $unsent->count(),
            'unsentAmount' => $this->formatAmounts($unsent, fn (Invoice $i) => $i->total_minor),
            'monthLabel' => now()->format('F').' sales',
            'monthSales' => $this->formatAmounts($paidThisMonth, fn (Invoice $i) => $i->total_minor),
            'fyLabel' => 'Tax year sales '.$fyStart->year,
            'fyCount' => $fyInvoices->count(),
            'fySales' => Money::format($fyGbpTotal, 'GBP'),
            'fyAverage' => Money::format($monthlyAverage, 'GBP'),
            'fyAverageMinor' => $monthlyAverage,
            'monthlyBars' => $monthlyBars,
            'maxBar' => $maxBar,
            'invoices' => $recent,
            'entitySubtitle' => $entityNames->implode(' · ') ?: 'Billing',
        ]);
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @param  callable(Invoice): int  $amount
     */
    private function formatAmounts($invoices, callable $amount): string
    {
        $byCurrency = $invoices->groupBy('currency')->map(
            fn ($group, $currency) => Money::format($group->sum($amount), $currency)
        );

        return $byCurrency->implode(' · ') ?: Money::format(0, 'GBP');
    }
}
