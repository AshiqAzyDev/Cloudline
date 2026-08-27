<div class="page">
    <div class="page-header">
        <div>
            <div class="page-title">{{ $invoiceId ? 'Edit invoice' : 'New invoice' }}</div>
            @if ($notesOnly)
                <p class="mt-1 text-sm text-muted">Paid invoices are locked — you can only update notes.</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ $invoiceId ? route('invoices.show', $invoiceId) : route('dashboard') }}" class="btn btn-ghost">Cancel</a>
            <button wire:click="saveDraft" class="btn btn-secondary" wire:loading.attr="disabled">
                {{ $notesOnly ? 'Save notes' : 'Save as draft' }}
            </button>
            @unless ($notesOnly)
                <button wire:click="saveAndEmail" class="btn btn-primary" wire:loading.attr="disabled">Save &amp; email to client</button>
            @endunless
        </div>
    </div>

    <x-error-summary />

    <div class="card card-pad mb-3" @if($notesOnly) aria-disabled="true" @endif>
        <div class="section-label">Bill to</div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="field-label">Entity</label>
                <select wire:model.live="billing_entity_id" class="field" @disabled($notesOnly)>
                    <option value="">Select entity</option>
                    @foreach ($entities as $entity)
                        <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                    @endforeach
                </select>
                @error('billing_entity_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($entities->isEmpty())
                    <p class="mt-1 text-sm text-amber-700">No billing entities found. Add one in Settings first.</p>
                @endif
            </div>
            <div>
                <label class="field-label">Client</label>
                <div class="flex gap-2">
                    <select wire:model.live="client_id" class="field" @disabled($notesOnly)>
                        <option value="">Select a client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company }}</option>
                        @endforeach
                    </select>
                    @unless ($notesOnly)
                        @can('create', App\Models\Client::class)
                            <button type="button" wire:click="$toggle('showQuickClient')" class="btn btn-ghost">+</button>
                        @endcan
                    @endunless
                </div>
                @error('client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Currency</label>
                <select wire:model.live="currency" class="field" @disabled($notesOnly)>
                    @foreach ($currencies as $code => $meta)
                        <option value="{{ $code }}">{{ $code }} — {{ $meta['name'] }}</option>
                    @endforeach
                </select>
                @error('currency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Issue date</label>
                <input type="date" wire:model.live="issue_date" class="field" @disabled($notesOnly)>
                @error('issue_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Due date</label>
                <input type="date" wire:model="due_date" class="field" @disabled($notesOnly)>
                @unless ($notesOnly)
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ([0 => 'Same day', 7 => '7 days', 14 => '14 days', 30 => '30 days'] as $days => $label)
                            <button type="button" wire:click="setDuePreset({{ $days }})" class="chip">{{ $label }}</button>
                        @endforeach
                    </div>
                @endunless
                @error('due_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- VAT treatment kept in state for persistence; primary UX is Include VAT toggle below. --}}
        <input type="hidden" wire:model="vat_treatment">

        @if ($showQuickClient && ! $notesOnly)
            <div class="mt-4 rounded-lg border border-dashed border-line p-4">
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <input wire:model="quick_company" placeholder="Company" class="field">
                        @error('quick_company') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input wire:model="quick_contact" placeholder="Contact" class="field">
                        @error('quick_contact') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input wire:model="quick_email" placeholder="Email" class="field">
                        @error('quick_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button type="button" wire:click="createQuickClient" class="btn btn-primary mt-3">Add client</button>
            </div>
        @endif
    </div>

    <div class="card card-pad mb-3">
        <div class="mb-3 flex items-center justify-between">
            <div class="section-label mb-0">Services</div>
        </div>
        @unless ($notesOnly)
            <div class="mb-3 flex flex-wrap gap-1.5">
                @forelse ($matchingServices as $service)
                    <button type="button" wire:click="addPreset({{ $service->id }})" class="chip !border-0 bg-brand-soft text-ink">+ {{ $service->name }}</button>
                @empty
                    <span class="text-sm text-muted">No catalogue services in {{ $currency }}. Use custom lines below.</span>
                @endforelse
                <button type="button" wire:click="addCustomItem" class="chip border-dashed">+ Custom service</button>
            </div>
        @endunless

        <div class="grid grid-cols-[1fr_1fr_70px_100px_100px_32px] gap-2.5 px-1 pb-2 text-xs font-semibold text-subtle">
            <div>Service</div><div>Description</div><div>Qty</div><div>Rate</div><div>Amount</div><div></div>
        </div>
        @foreach ($items as $index => $item)
            <div class="mb-2" wire:key="item-{{ $index }}">
                <div class="grid grid-cols-[1fr_1fr_70px_100px_100px_32px] items-start gap-2.5 px-1">
                    <select wire:change="applyService({{ $index }}, $event.target.value)" class="field @error('items.'.$index.'.description') border-red-400 @enderror" @disabled($notesOnly)>
                        <option value="custom">Custom</option>
                        @foreach ($matchingServices as $service)
                            <option value="{{ $service->id }}" @selected(($item['service_id'] ?? null) == $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <div>
                        <input wire:model="items.{{ $index }}.description" placeholder="Service description" class="field @error('items.'.$index.'.description') border-red-400 @enderror" @disabled($notesOnly)>
                        @error('items.'.$index.'.description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input wire:model.live="items.{{ $index }}.qty" type="number" min="0" step="0.01" class="field @error('items.'.$index.'.qty') border-red-400 @enderror" @disabled($notesOnly)>
                        @error('items.'.$index.'.qty') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input wire:model.live="items.{{ $index }}.unit_price" type="number" min="0" step="0.01" class="field @error('items.'.$index.'.unit_price') border-red-400 @enderror" @disabled($notesOnly)>
                        @error('items.'.$index.'.unit_price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="py-2 text-sm font-semibold">{{ \App\Support\Money::format((int) round(((float) ($item['qty'] ?: 0)) * \App\Support\Money::toMinor($item['unit_price'] ?? 0, $currency)), $currency) }}</div>
                    @unless ($notesOnly)
                        <button type="button" wire:click="removeItem({{ $index }})" class="py-2 text-center text-subtle hover:text-red-600" title="Remove line">✕</button>
                    @else
                        <div></div>
                    @endunless
                </div>
            </div>
        @endforeach
        @error('items') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="card card-pad">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <button type="button" wire:click="$toggle('vat_enabled')" class="relative h-[22px] w-[38px] cursor-pointer rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-50" style="background: {{ $vat_enabled ? '#0F766E' : '#D4D4D8' }}" @disabled($notesOnly)>
                    <span class="absolute top-[2px] h-[18px] w-[18px] rounded-full bg-white shadow" style="left: {{ $vat_enabled ? '18px' : '2px' }}"></span>
                </button>
                <span class="text-sm font-semibold">Include VAT</span>
            </div>
            @if ($vat_enabled)
                <div class="flex items-center gap-1.5">
                    <input wire:model.live="vat_rate" type="number" min="0" max="100" class="field w-[60px] text-right" @disabled($notesOnly)>
                    <span class="text-sm text-label">%</span>
                </div>
            @endif
        </div>
        @error('vat_rate') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        <div class="my-4 h-px bg-[#F0F0F0]"></div>
        <div class="flex flex-col items-end gap-2">
            <div class="flex gap-10 text-sm text-label"><div class="w-[100px]">Subtotal</div><div class="w-[100px] text-right">{{ \App\Support\Money::format($totals['subtotal_minor'], $currency) }}</div></div>
            @if ($totals['vat_enabled'])
                <div class="flex gap-10 text-sm text-label"><div class="w-[100px]">VAT ({{ $vat_rate }}%)</div><div class="w-[100px] text-right">{{ \App\Support\Money::format($totals['vat_minor'], $currency) }}</div></div>
            @endif
            <div class="mt-1 flex gap-10 text-[17px] font-bold"><div class="w-[100px]">Total</div><div class="w-[100px] text-right">{{ \App\Support\Money::format($totals['total_minor'], $currency) }}</div></div>
        </div>
        <div class="mt-6">
            <label class="field-label">Notes</label>
            <textarea wire:model="notes" rows="3" class="field"></textarea>
        </div>
        @unless ($notesOnly)
            <div class="mt-4">
                <label class="field-label">Payment terms</label>
                <textarea wire:model="terms" rows="2" class="field"></textarea>
            </div>
        @endunless
    </div>
</div>
