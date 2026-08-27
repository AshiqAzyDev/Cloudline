<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Services\ReportService;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_only_includes_invoices_inside_the_financial_year(): void
    {
        Invoice::factory()->sent()->create([
            'issue_date' => '2026-03-31',
            'status' => InvoiceStatus::Sent,
            'currency' => 'GBP',
            'total_minor' => 10000,
            'number' => 'CT-2025-0099',
        ]);

        Invoice::factory()->sent()->create([
            'issue_date' => '2026-04-01',
            'status' => InvoiceStatus::Sent,
            'currency' => 'GBP',
            'total_minor' => 25000,
            'number' => 'CT-2026-0001',
        ]);

        $report = app(ReportService::class)->forFinancialYear(2026);
        $gbp = collect($report['by_currency'])->firstWhere('currency', 'GBP');

        $this->assertSame(2026, FinancialYear::startFor(now()->setDate(2026, 4, 1))->year);
        $this->assertSame(25000, $gbp['invoiced']);
        $this->assertSame('FY 2026/27', $report['label']);
    }

    public function test_by_service_keeps_currencies_separate(): void
    {
        $service = Service::factory()->create(['name' => 'Design']);

        $gbpInvoice = Invoice::factory()->sent()->create([
            'issue_date' => '2026-05-01',
            'currency' => 'GBP',
            'total_minor' => 10000,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $gbpInvoice->id,
            'service_id' => $service->id,
            'description' => 'Design',
            'amount_minor' => 10000,
        ]);

        $usdInvoice = Invoice::factory()->sent()->create([
            'issue_date' => '2026-06-01',
            'currency' => 'USD',
            'total_minor' => 10000,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $usdInvoice->id,
            'service_id' => $service->id,
            'description' => 'Design',
            'amount_minor' => 10000,
        ]);

        $rows = collect(app(ReportService::class)->forFinancialYear(2026)['by_service'])
            ->where('name', 'Design')
            ->values();

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            [
                ['name' => 'Design', 'currency' => 'GBP', 'amount' => 10000],
                ['name' => 'Design', 'currency' => 'USD', 'amount' => 10000],
            ],
            $rows->map(fn ($row) => [
                'name' => $row['name'],
                'currency' => $row['currency'],
                'amount' => $row['amount'],
            ])->all()
        );
    }
}
