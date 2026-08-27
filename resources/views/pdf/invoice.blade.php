<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #171717; font-size: 13px; }
        h1 { font-size: 22px; margin: 0 0 6px; }
        .muted { color: #71717A; }
        .row { width: 100%; margin-bottom: 24px; }
        .col { width: 48%; display: inline-block; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; margin: 18px 0; }
        th { text-align: left; font-size: 11px; color: #71717A; border-bottom: 1px solid #E5E5E5; padding: 8px 6px; }
        td { padding: 10px 6px; border-bottom: 1px solid #F0F0F0; }
        .right { text-align: right; }
        .totals { width: 280px; margin-left: auto; }
        .totals td { border: 0; padding: 4px 6px; }
        .total { font-size: 16px; font-weight: bold; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .footer { margin-top: 30px; font-size: 11px; color: #71717A; }
        pre { font-family: DejaVu Sans, sans-serif; white-space: pre-wrap; }
    </style>
</head>
<body>
    <table class="row">
        <tr>
            <td>
                <h1>{{ $invoice->displayNumber() }}</h1>
                <div class="muted">Issued {{ $invoice->issue_date->format('d M Y') }} · Due {{ $invoice->due_date->format('d M Y') }}</div>
            </td>
            <td class="right">
                <span class="pill" style="background: {{ $invoice->status->background() }}; color: {{ $invoice->status->color() }}">{{ $invoice->status->label() }}</span>
            </td>
        </tr>
    </table>

    <div class="row">
        <div class="col">
            <div class="muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px">From</div>
            <strong>{{ $invoice->billingEntity->legal_name }}</strong><br>
            {!! nl2br(e($invoice->billingEntity->formattedAddress())) !!}<br>
            {{ $invoice->billingEntity->email }}
            @if ($invoice->billingEntity->vat_number)<br>VAT {{ $invoice->billingEntity->vat_number }}@endif
        </div>
        <div class="col">
            <div class="muted" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px">Bill to</div>
            <strong>{{ $invoice->client->company }}</strong><br>
            {{ $invoice->client->contact }}<br>
            {{ $invoice->client->email }}<br>
            {!! nl2br(e($invoice->client->address)) !!}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->qty }}</td>
                    <td class="right">{{ $item->formattedUnitPrice() }}</td>
                    <td class="right"><strong>{{ $item->formattedAmount() }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ $invoice->formattedSubtotal() }}</td></tr>
        @if ($invoice->vat_enabled)
            <tr><td>VAT ({{ $invoice->vat_rate }}%)</td><td class="right">{{ $invoice->formattedVat() }}</td></tr>
        @endif
        <tr class="total"><td>Total</td><td class="right">{{ $invoice->formattedTotal() }}</td></tr>
    </table>

    @if ($invoice->vat_treatment->invoiceNote())
        <p>{{ $invoice->vat_treatment->invoiceNote() }}</p>
    @endif

    @if ($invoice->billingEntity->formattedBankDetails() !== '')
        <div class="footer">
            <strong>Bank transfer</strong>
            <pre>{{ $invoice->billingEntity->formattedBankDetails() }}</pre>
        </div>
    @endif

    @if ($invoice->terms)
        <div class="footer">{{ $invoice->terms }}</div>
    @endif

    @if ($invoice->billingEntity->invoice_footer)
        <div class="footer">{{ $invoice->billingEntity->invoice_footer }}</div>
    @endif

    <div class="footer">Pay online: {{ route('pay.show', $invoice->pay_token) }}</div>
</body>
</html>
