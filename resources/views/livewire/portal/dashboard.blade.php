<div>
    <div class="mb-6 text-2xl font-bold">Your invoices</div>
    <div class="card overflow-hidden">
        @forelse ($invoices as $invoice)
            <a href="{{ route('portal.invoices.show', $invoice) }}" class="flex items-center justify-between border-b border-[#F0F0F0] px-5 py-3.5 text-sm hover:bg-white">
                <div>
                    <div class="font-semibold">{{ $invoice->displayNumber() }}</div>
                    <div class="text-xs text-subtle">{{ $invoice->billingEntity->name }} · Due {{ $invoice->due_date->format('d M Y') }}</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="font-semibold">{{ $invoice->formattedTotal() }}</span>
                    <x-status-pill :status="$invoice->status" />
                </div>
            </a>
        @empty
            <div class="px-5 py-10 text-center text-sm text-muted">No invoices yet.</div>
        @endforelse
    </div>
</div>
