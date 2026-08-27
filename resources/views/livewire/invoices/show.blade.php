<div class="page">
    <a href="{{ route('invoices.index') }}" class="mb-3 inline-block text-[12.5px] text-muted hover:text-ink">‹ All invoices</a>
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1.55fr_1fr]">
        <div class="card card-pad">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <div class="font-display text-[1.35rem] font-semibold tracking-tight">{{ $invoice->displayNumber() }}</div>
                    <div class="mt-0.5 text-[12px] text-muted">Issued {{ $invoice->issue_date->format('M j, Y') }} · Due {{ $invoice->due_date->format('M j, Y') }}</div>
                </div>
                <x-status-pill :status="$invoice->status" />
            </div>
            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <div class="section-label">From</div>
                    <div class="text-[13px] font-semibold">{{ $invoice->billingEntity?->legal_name }}</div>
                    <div class="text-[12.5px] leading-5 text-muted">@if($invoice->billingEntity){!! nl2br(e($invoice->billingEntity->formattedAddress())) !!}<br>{{ $invoice->billingEntity->email }}@endif</div>
                </div>
                <div>
                    <div class="section-label">Bill to</div>
                    <div class="text-[13px] font-semibold">{{ $invoice->client?->company }}</div>
                    <div class="text-[12.5px] leading-5 text-muted">{{ $invoice->client?->contact }}<br>{{ $invoice->client?->email }}<br>{{ $invoice->client?->address }}</div>
                </div>
            </div>
            <div class="mb-3 overflow-hidden rounded-md border border-line">
                <div class="grid grid-cols-[1fr_56px_88px_88px] bg-canvas px-3 py-2 text-[11px] font-semibold text-muted">
                    <div>Description</div><div>Qty</div><div>Rate</div><div>Amount</div>
                </div>
                @forelse ($invoice->items as $item)
                    <div class="grid grid-cols-[1fr_56px_88px_88px] border-t border-line px-3 py-2 text-[12.5px]">
                        <div>{{ $item->description }}</div>
                        <div class="text-muted">{{ $item->qty }}</div>
                        <div class="text-muted">{{ $item->formattedUnitPrice() }}</div>
                        <div class="font-semibold">{{ $item->formattedAmount() }}</div>
                    </div>
                @empty
                    <div class="border-t border-line px-3 py-2 text-[12.5px] text-muted">No line items.</div>
                @endforelse
            </div>
            <div class="flex flex-col items-end gap-1.5">
                <div class="flex gap-8 text-[12.5px] text-label"><div class="w-[96px]">Subtotal</div><div class="w-[96px] text-right">{{ $invoice->formattedSubtotal() }}</div></div>
                @if ($invoice->vat_enabled)
                    <div class="flex gap-8 text-[12.5px] text-label"><div class="w-[96px]">VAT ({{ $invoice->vat_rate }}%)</div><div class="w-[96px] text-right">{{ $invoice->formattedVat() }}</div></div>
                @endif
                <div class="flex gap-8 text-[15px] font-bold"><div class="w-[96px]">Total</div><div class="w-[96px] text-right">{{ $invoice->formattedTotal() }}</div></div>
            </div>
            @if ($invoice->vat_treatment?->invoiceNote())
                <p class="mt-3 text-[12px] text-muted">{{ $invoice->vat_treatment->invoiceNote() }}</p>
            @endif
        </div>

        <div class="flex flex-col gap-3">
            <div class="card card-pad">
                <div class="section-label">Actions</div>
                <div class="flex flex-col gap-1.5">
                    <a href="{{ $payUrl }}" target="_blank" class="btn btn-primary w-full">View payment page →</a>
                    @can('send', $invoice)
                        <button wire:click="send" class="btn btn-secondary w-full">Email invoice to client</button>
                    @endcan
                    <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-secondary w-full">Download as PDF</a>
                    @can('update', $invoice)
                        @if ($invoice->isEditable())
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-secondary w-full">Edit invoice</a>
                        @endif
                        @if ($invoice->status->value === 'awaiting_verification')
                            <button wire:click="markPaid" class="btn btn-primary w-full">Confirm bank payment</button>
                        @elseif ($invoice->isPayable() || $invoice->status->value === 'draft')
                            <button wire:click="markPaid" class="btn btn-secondary w-full">Mark as paid manually</button>
                        @endif
                    @endcan
                    @can('create', App\Models\Invoice::class)
                        <button wire:click="duplicate" class="btn btn-secondary w-full">Duplicate</button>
                    @endcan
                    @can('void', $invoice)
                        @if ($invoice->status->value !== 'paid' && $invoice->status->value !== 'void')
                            <button wire:click="void" wire:confirm="Void this invoice?" class="btn btn-ghost w-full text-red-700">Void</button>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="card card-pad">
                <div class="section-label">Payment reminders</div>
                @forelse ($invoice->reminders as $reminder)
                    <div class="flex items-center gap-2 border-t border-line py-1.5 first:border-0">
                        <div class="flex h-5 w-5 items-center justify-center rounded text-[10px]" style="background: {{ $reminder->sent_at ? '#DCFCE7' : '#F5F5F5' }}; color: {{ $reminder->sent_at ? '#15803D' : '#A1A1AA' }}">
                            {{ $reminder->sent_at ? '✓' : '·' }}
                        </div>
                        <div class="text-[12.5px]">
                            Day {{ $reminder->offset_days }}
                            <div class="text-[11px] text-subtle">
                                @if ($invoice->status->value === 'paid')
                                    Not needed — invoice paid
                                @elseif ($reminder->cancelled_at)
                                    Cancelled
                                @elseif ($reminder->sent_at)
                                    {{ $reminder->is_manual ? 'Sent manually' : 'Sent automatically' }}
                                @else
                                    Scheduled {{ $reminder->scheduled_for?->format('d M') }}
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-[12.5px] text-muted">No reminders scheduled.</p>
                @endforelse
                @can('send', $invoice)
                    @if ($invoice->isPayable())
                        <button wire:click="remind" class="btn btn-secondary mt-2 w-full">Send reminder now</button>
                    @endif
                @endcan
            </div>

            <div class="card card-pad">
                <div class="section-label">Activity</div>
                @forelse ($invoice->events as $event)
                    <div class="border-t border-line py-1.5 text-[12.5px] first:border-0">
                        <div class="font-medium">{{ $event->type->label() }}</div>
                        <div class="text-[11px] text-subtle">{{ $event->created_at->timezone(config('app.timezone'))->format('d M Y H:i') }} @if($event->user) · {{ $event->user->name }} @endif</div>
                    </div>
                @empty
                    <p class="text-[12.5px] text-muted">No activity yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
