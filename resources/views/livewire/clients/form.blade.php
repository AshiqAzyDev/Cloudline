<div class="page">
    <div class="page-title mb-3">{{ $clientId ? 'Edit client' : 'New client' }}</div>
    <x-error-summary />
    <form wire:submit="save" class="card card-pad space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="field-label">Company name</label>
                <input wire:model="company" class="field">
                @error('company') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Contact name</label>
                <input wire:model="contact" class="field">
                @error('contact') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Email</label>
                <input wire:model="email" class="field">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Phone</label>
                <input wire:model="phone" class="field">
                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Country (ISO)</label>
                <input wire:model="country" class="field" maxlength="2" placeholder="GB">
                @error('country') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="field-label">Billing address</label>
                <textarea wire:model="address" rows="2" class="field"></textarea>
                @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">VAT number</label>
                <input wire:model="vat_number" class="field">
                @error('vat_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Default currency</label>
                <select wire:model="default_currency" class="field">
                    @foreach ($currencies as $code => $meta)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
                @error('default_currency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="field-label">VAT treatment</label>
                <select wire:model="vat_treatment" class="field">
                    @foreach ($treatments as $treatment)
                        <option value="{{ $treatment->value }}">{{ $treatment->label() }}</option>
                    @endforeach
                </select>
                @error('vat_treatment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="col-span-2">
                <label class="field-label">Notes</label>
                <textarea wire:model="notes" rows="3" class="field"></textarea>
                @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="flex gap-2">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('clients.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
