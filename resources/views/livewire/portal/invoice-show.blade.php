<div>
    <a href="{{ route('portal.dashboard') }}" class="mb-5 inline-block text-[13px] text-muted">‹ Your invoices</a>
    <div class="card card-pad">
        <div class="page-header">
            <div>
                <div class="text-[22px] font-bold">{{ $invoice->displayNumber() }}</div>
                <div class="mt-1 text-sm text-muted">Due {{ $invoice->due_date->format('d M Y') }}</div>
            </div>
            <x-status-pill :status="$invoice->status" />
        </div>
        <div class="mb-6 text-3xl font-bold">{{ $invoice->formattedTotal() }}</div>
        @foreach ($invoice->items as $item)
            <div class="flex justify-between border-t border-[#F0F0F0] py-3 text-sm">
                <span>{{ $item->description }}</span>
                <span class="font-semibold">{{ $item->formattedAmount() }}</span>
            </div>
        @endforeach
        <div class="mt-6 flex gap-2">
            @if ($invoice->isPayable())
                <a href="{{ route('pay.show', $invoice->pay_token) }}" class="btn btn-primary">Pay now</a>
            @endif
            <a href="{{ route('portal.invoices.pdf', $invoice) }}" class="btn btn-secondary">Download PDF</a>
        </div>
    </div>
</div>
