<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\ReportService;
use App\Support\FinancialYear;
use App\Support\Money;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): StreamedResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = [
            'fy' => (int) $request->integer('fy', FinancialYear::startFor(now())->year),
            'date_from' => $request->string('date_from')->toString() ?: null,
            'date_to' => $request->string('date_to')->toString() ?: null,
            'entity_id' => $request->integer('entity_id') ?: null,
            'client_id' => $request->integer('client_id') ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'currency' => $request->string('currency')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: null,
            'net' => $request->boolean('net'),
            'include_drafts' => $request->boolean('include_drafts'),
        ];

        $data = $reports->build($filters);
        $filename = 'cloudline-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Cloudline filtered report']);
            fputcsv($out, ['Period', $data['label']]);
            fputcsv($out, ['Net of fees', $data['net_of_fees'] ? 'Yes' : 'No']);
            fputcsv($out, ['Invoice count', count($data['invoice_rows'])]);
            fputcsv($out, []);

            fputcsv($out, ['Invoices']);
            fputcsv($out, [
                'Number', 'Entity', 'Client', 'Status', 'Issue date', 'Due date', 'Currency',
                'Subtotal', 'VAT', 'Total', 'Paid', 'Outstanding',
            ]);
            foreach ($data['invoice_rows'] as $row) {
                fputcsv($out, [
                    $row['number'],
                    $row['entity'],
                    $row['client'],
                    $row['status'],
                    $row['issue_date'],
                    $row['due_date'],
                    $row['currency'],
                    $row['subtotal_fmt'],
                    $row['vat_fmt'],
                    $row['total_fmt'],
                    $row['paid_fmt'],
                    $row['outstanding_fmt'],
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['By currency']);
            fputcsv($out, ['Currency', 'Invoiced', 'Received', 'Outstanding', 'Overdue']);
            foreach ($data['by_currency'] as $row) {
                fputcsv($out, [$row['currency'], $row['invoiced_fmt'], $row['received_fmt'], $row['outstanding_fmt'], $row['overdue_fmt']]);
            }

            fputcsv($out, []);
            fputcsv($out, ['By status']);
            fputcsv($out, ['Status', 'Count', 'Amount']);
            foreach ($data['by_status'] as $row) {
                $amount = $row['currency'] === 'MIXED'
                    ? number_format($row['amount'] / 100, 2)
                    : Money::format($row['amount'], $row['currency']);
                fputcsv($out, [$row['name'], $row['count'], $amount]);
            }

            fputcsv($out, []);
            fputcsv($out, ['By entity']);
            fputcsv($out, ['Entity', 'Invoiced']);
            foreach ($data['by_entity'] as $row) {
                $amount = $row['currency'] === 'MIXED'
                    ? number_format($row['invoiced'] / 100, 2)
                    : Money::format($row['invoiced'], $row['currency']);
                fputcsv($out, [$row['name'], $amount]);
            }

            fputcsv($out, []);
            fputcsv($out, ['By client']);
            fputcsv($out, ['Client', 'Invoiced']);
            foreach ($data['by_client'] as $row) {
                $amount = $row['currency'] === 'MIXED'
                    ? number_format($row['invoiced'] / 100, 2)
                    : Money::format($row['invoiced'], $row['currency']);
                fputcsv($out, [$row['name'], $amount]);
            }

            fputcsv($out, []);
            fputcsv($out, ['By service']);
            fputcsv($out, ['Service', 'Currency', 'Amount']);
            foreach ($data['by_service'] as $row) {
                fputcsv($out, [$row['name'], $row['currency'], Money::format($row['amount'], $row['currency'])]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Monthly (GBP indicative)']);
            fputcsv($out, ['Month', 'Invoiced GBP', 'Received GBP']);
            foreach ($data['monthly'] as $row) {
                fputcsv($out, [$row['label'], $row['invoiced_gbp'] / 100, $row['received_gbp'] / 100]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
