<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\BillingEntity;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_can_filter_by_entity_status_and_currency(): void
    {
        $ct = BillingEntity::factory()->create(['invoice_prefix' => 'CT', 'name' => 'Cloud Technologies']);
        $cdm = BillingEntity::factory()->create(['invoice_prefix' => 'CDM', 'name' => 'Cloud Digital Marketing']);
        $client = Client::factory()->create(['company' => 'Filter Co']);

        Invoice::factory()->sent()->create([
            'billing_entity_id' => $ct->id,
            'client_id' => $client->id,
            'currency' => 'GBP',
            'status' => InvoiceStatus::Sent,
            'issue_date' => '2026-05-01',
            'total_minor' => 10000,
            'number' => 'CT-2026-0001',
        ]);
        Invoice::factory()->sent()->create([
            'billing_entity_id' => $cdm->id,
            'client_id' => $client->id,
            'currency' => 'INR',
            'status' => InvoiceStatus::Overdue,
            'issue_date' => '2026-06-01',
            'total_minor' => 50000,
            'number' => 'CDM-2026-0001',
        ]);

        $filtered = app(ReportService::class)->build([
            'fy' => 2026,
            'entity_id' => $ct->id,
            'currency' => 'GBP',
            'status' => InvoiceStatus::Sent->value,
        ]);

        $this->assertCount(1, $filtered['invoice_rows']);
        $this->assertSame('CT-2026-0001', $filtered['invoice_rows'][0]['number']);
    }

    public function test_export_uses_the_same_filters_as_the_report(): void
    {
        $entity = BillingEntity::factory()->create();
        $client = Client::factory()->create(['company' => 'Export Client']);
        Invoice::factory()->sent()->create([
            'billing_entity_id' => $entity->id,
            'client_id' => $client->id,
            'issue_date' => '2026-05-10',
            'number' => 'CT-2026-0099',
            'status' => InvoiceStatus::Sent,
        ]);

        $response = $this->actingAs($this->admin())->get(route('reports.export', [
            'fy' => 2026,
            'entity_id' => $entity->id,
            'search' => 'Export Client',
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('CT-2026-0099', $response->streamedContent());
        $this->assertStringContainsString('Export Client', $response->streamedContent());
    }

    public function test_custom_date_range_overrides_financial_year(): void
    {
        Invoice::factory()->sent()->create([
            'issue_date' => '2026-05-01',
            'number' => 'CT-2026-0100',
            'status' => InvoiceStatus::Sent,
        ]);
        Invoice::factory()->sent()->create([
            'issue_date' => '2026-08-01',
            'number' => 'CT-2026-0101',
            'status' => InvoiceStatus::Sent,
        ]);

        $filtered = app(ReportService::class)->build([
            'fy' => 2026,
            'date_from' => '2026-07-01',
            'date_to' => '2026-08-31',
        ]);

        $numbers = collect($filtered['invoice_rows'])->pluck('number')->all();
        $this->assertContains('CT-2026-0101', $numbers);
        $this->assertNotContains('CT-2026-0100', $numbers);
    }

    public function test_available_financial_years_expand_with_invoice_issue_dates(): void
    {
        Invoice::factory()->sent()->create([
            'issue_date' => '2024-06-01',
            'status' => InvoiceStatus::Sent,
        ]);
        Invoice::factory()->sent()->create([
            'issue_date' => '2026-05-01',
            'status' => InvoiceStatus::Sent,
        ]);

        $this->travelTo('2026-08-15');

        $years = app(ReportService::class)->availableFinancialYears();

        $this->assertSame([2026, 2025, 2024], $years);
    }
}
