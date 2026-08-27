<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\FinancialYear;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @param  array{
     *     fy?: int|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     entity_id?: int|null,
     *     client_id?: int|null,
     *     status?: string|null,
     *     currency?: string|null,
     *     search?: string|null,
     *     net?: bool,
     *     include_drafts?: bool
     * }  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters = []): array
    {
        $netOfFees = (bool) ($filters['net'] ?? false);
        [$from, $to, $label] = $this->resolvePeriod($filters);

        $invoiceQuery = $this->invoiceQuery($filters, $from, $to);
        $invoices = (clone $invoiceQuery)
            ->with(['client', 'billingEntity', 'items.service', 'payments'])
            ->latest('issue_date')
            ->get();

        $invoiceIds = $invoices->pluck('id');

        $payments = Payment::query()
            ->whereIn('invoice_id', $invoiceIds)
            ->whereBetween('received_at', [$from, $to->endOfDay()])
            ->get();

        $openStatuses = [
            InvoiceStatus::Sent->value,
            InvoiceStatus::Overdue->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::AwaitingVerification->value,
        ];

        $open = $invoices->filter(fn (Invoice $invoice) => in_array($invoice->status->value, $openStatuses, true));

        return [
            'label' => $label,
            'from' => $from,
            'to' => $to,
            'net_of_fees' => $netOfFees,
            'filters' => $filters,
            'invoices' => $invoices,
            'invoice_rows' => $this->invoiceRows($invoices),
            'by_currency' => $this->byCurrency($invoices, $payments, $open, $netOfFees),
            'monthly' => $this->monthly($invoices, $payments, $from, $to, $netOfFees),
            'by_entity' => $this->groupSum($invoices, $payments, $netOfFees, fn (Invoice $invoice) => $invoice->billingEntity->name),
            'by_client' => $this->groupSum($invoices, $payments, $netOfFees, fn (Invoice $invoice) => $invoice->client->company),
            'by_service' => $this->byService($invoices),
            'by_status' => $this->byStatus($invoices),
            'gbp_indicative_invoiced' => $invoices->sum(fn (Invoice $invoice) => $invoice->total_gbp_minor ?? ($invoice->currency === 'GBP' ? $invoice->total_minor : 0)),
            'gbp_received' => $payments->sum(function (Payment $payment) use ($netOfFees) {
                if (strtoupper((string) $payment->settlement_currency) === 'GBP' && $payment->settlement_amount_minor) {
                    return $netOfFees ? ($payment->settlement_amount_minor - $payment->fee_minor) : $payment->settlement_amount_minor;
                }

                if ($payment->currency === 'GBP') {
                    return $netOfFees ? $payment->net_minor : $payment->amount_minor;
                }

                return 0;
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forFinancialYear(int $startYear, bool $netOfFees = false, ?int $entityId = null): array
    {
        return $this->build([
            'fy' => $startYear,
            'net' => $netOfFees,
            'entity_id' => $entityId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}
     */
    private function resolvePeriod(array $filters): array
    {
        $fromInput = filled($filters['date_from'] ?? null) ? CarbonImmutable::parse($filters['date_from'])->startOfDay() : null;
        $toInput = filled($filters['date_to'] ?? null) ? CarbonImmutable::parse($filters['date_to'])->endOfDay() : null;

        if ($fromInput || $toInput) {
            $from = $fromInput ?? CarbonImmutable::parse('2000-01-01')->startOfDay();
            $to = $toInput ?? now()->toImmutable()->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
            }

            return [$from, $to, $from->format('d M Y').' – '.$to->format('d M Y')];
        }

        $year = (int) ($filters['fy'] ?? FinancialYear::startFor(now())->year);
        [$from, $to] = FinancialYear::rangeForStartYear($year);

        return [$from, $to, FinancialYear::label($year)];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function invoiceQuery(array $filters, CarbonInterface $from, CarbonInterface $to): Builder
    {
        $includeDrafts = (bool) ($filters['include_drafts'] ?? false);
        $status = $filters['status'] ?? null;

        return Invoice::query()
            ->when(! $includeDrafts && blank($status), function (Builder $q) {
                $q->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Void->value]);
            })
            ->when(filled($status), fn (Builder $q) => $q->where('status', $status))
            ->when(! empty($filters['entity_id']), fn (Builder $q) => $q->where('billing_entity_id', $filters['entity_id']))
            ->when(! empty($filters['client_id']), fn (Builder $q) => $q->where('client_id', $filters['client_id']))
            ->when(filled($filters['currency'] ?? null), fn (Builder $q) => $q->where('currency', $filters['currency']))
            ->when(filled($filters['search'] ?? null), function (Builder $q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('client', fn (Builder $c) => $c->where('company', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]);
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return list<array<string, mixed>>
     */
    private function invoiceRows(Collection $invoices): array
    {
        return $invoices->map(fn (Invoice $invoice) => [
            'id' => $invoice->id,
            'number' => $invoice->displayNumber(),
            'entity' => $invoice->billingEntity->name,
            'prefix' => $invoice->billingEntity->invoice_prefix,
            'client' => $invoice->client->company,
            'status' => $invoice->status->label(),
            'status_value' => $invoice->status->value,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'currency' => $invoice->currency,
            'subtotal_minor' => $invoice->subtotal_minor,
            'vat_minor' => $invoice->vat_minor,
            'total_minor' => $invoice->total_minor,
            'paid_minor' => $invoice->amount_paid_minor,
            'outstanding_minor' => $invoice->outstandingMinor(),
            'subtotal_fmt' => Money::format($invoice->subtotal_minor, $invoice->currency),
            'vat_fmt' => Money::format($invoice->vat_minor, $invoice->currency),
            'total_fmt' => Money::format($invoice->total_minor, $invoice->currency),
            'paid_fmt' => Money::format($invoice->amount_paid_minor, $invoice->currency),
            'outstanding_fmt' => Money::format($invoice->outstandingMinor(), $invoice->currency),
        ])->all();
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return list<array{name: string, count: int, amount: int, currency: string}>
     */
    private function byStatus(Collection $invoices): array
    {
        return $invoices->groupBy(fn (Invoice $invoice) => $invoice->status->value)
            ->map(function (Collection $group, string $status) {
                /** @var InvoiceStatus $enum */
                $enum = InvoiceStatus::from($status);

                return [
                    'name' => $enum->label(),
                    'count' => $group->count(),
                    'amount' => $group->sum('total_minor'),
                    'currency' => $group->pluck('currency')->unique()->count() === 1 ? $group->first()->currency : 'MIXED',
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, Invoice>  $open
     * @return list<array<string, mixed>>
     */
    private function byCurrency(Collection $invoices, Collection $payments, Collection $open, bool $netOfFees): array
    {
        $currencies = $invoices->pluck('currency')
            ->merge($payments->pluck('currency'))
            ->merge($open->pluck('currency'))
            ->unique()
            ->sort()
            ->values();

        return $currencies->map(function (string $currency) use ($invoices, $payments, $open, $netOfFees) {
            $invoiced = $invoices->where('currency', $currency)->sum('total_minor');
            $received = $payments->where('currency', $currency)->sum(fn (Payment $p) => $netOfFees ? $p->net_minor : $p->amount_minor);
            $outstanding = $open->where('currency', $currency)->sum(fn (Invoice $i) => $i->outstandingMinor());
            $overdue = $open->where('currency', $currency)
                ->where('status', InvoiceStatus::Overdue)
                ->sum(fn (Invoice $i) => $i->outstandingMinor());

            return [
                'currency' => $currency,
                'invoiced' => $invoiced,
                'received' => $received,
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'invoiced_fmt' => Money::format($invoiced, $currency),
                'received_fmt' => Money::format($received, $currency),
                'outstanding_fmt' => Money::format($outstanding, $currency),
                'overdue_fmt' => Money::format($overdue, $currency),
            ];
        })->all();
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @param  Collection<int, Payment>  $payments
     * @return list<array<string, mixed>>
     */
    private function monthly(Collection $invoices, Collection $payments, CarbonInterface $from, CarbonInterface $to, bool $netOfFees): array
    {
        $cursor = $from->toImmutable()->startOfMonth();
        $end = $to->toImmutable()->endOfMonth();
        $rows = [];

        while ($cursor->lte($end)) {
            $monthInvoices = $invoices->filter(fn (Invoice $i) => $i->issue_date->isSameMonth($cursor));
            $monthPayments = $payments->filter(fn (Payment $p) => $p->received_at->isSameMonth($cursor));
            $rows[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('M Y'),
                'invoiced_gbp' => $monthInvoices->sum(fn (Invoice $i) => $i->currency === 'GBP' ? $i->total_minor : ($i->total_gbp_minor ?? 0)),
                'received_gbp' => $monthPayments->where('currency', 'GBP')->sum(fn (Payment $p) => $netOfFees ? $p->net_minor : $p->amount_minor),
            ];
            $cursor = $cursor->addMonth();
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @param  Collection<int, Payment>  $payments
     * @return list<array<string, mixed>>
     */
    private function groupSum(Collection $invoices, Collection $payments, bool $netOfFees, callable $label): array
    {
        return $invoices->groupBy($label)->map(function (Collection $group, string $name) use ($payments, $netOfFees) {
            $ids = $group->pluck('id');
            $received = $payments->whereIn('invoice_id', $ids)->sum(fn (Payment $p) => $netOfFees ? $p->net_minor : $p->amount_minor);

            return [
                'name' => $name,
                'invoiced' => $group->sum('total_minor'),
                'received' => $received,
                'currency' => $group->pluck('currency')->unique()->count() === 1 ? $group->first()->currency : 'MIXED',
            ];
        })->sortByDesc('invoiced')->values()->all();
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return list<array{name: string, currency: string, amount: int}>
     */
    private function byService(Collection $invoices): array
    {
        $rows = [];

        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $name = $item->service?->name ?? $item->description;
                $key = $name.'|'.$invoice->currency;
                $rows[$key]['name'] = $name;
                $rows[$key]['currency'] = $invoice->currency;
                $rows[$key]['amount'] = ($rows[$key]['amount'] ?? 0) + $item->amount_minor;
            }
        }

        return collect($rows)->sortByDesc('amount')->values()->all();
    }

    /**
     * FY start years for the reports dropdown, based on invoice issue dates and today.
     *
     * @return list<int>
     */
    public function availableFinancialYears(?CarbonInterface $now = null, ?int $selected = null): array
    {
        $bounds = Invoice::query()
            ->whereNotNull('issue_date')
            ->selectRaw('MIN(issue_date) as min_date, MAX(issue_date) as max_date')
            ->first();

        $earliest = $bounds?->min_date
            ? FinancialYear::startFor(CarbonImmutable::parse($bounds->min_date))->year
            : null;
        $latest = $bounds?->max_date
            ? FinancialYear::startFor(CarbonImmutable::parse($bounds->max_date))->year
            : null;

        $years = FinancialYear::availableStartYears($now, $earliest, $latest);

        if ($selected !== null && $selected > 0) {
            $years = FinancialYear::includeStartYear($years, $selected);
        }

        return $years;
    }
}
