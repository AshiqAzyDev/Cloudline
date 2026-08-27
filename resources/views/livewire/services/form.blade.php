<div class="page">
    <div class="page-title mb-3">{{ $serviceId ? 'Edit service' : 'New service' }}</div>
    <x-error-summary />
    <form wire:submit="save" class="card card-pad space-y-3">
        <div>
            <label class="field-label">Name</label>
            <input wire:model="name" class="field">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="field-label">Description</label>
            <textarea wire:model="description" rows="2" class="field"></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="field-label">Default rate</label>
                <input type="number" step="0.01" wire:model="default_rate" class="field">
                @error('default_rate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Currency</label>
                <select wire:model="currency" class="field">
                    @foreach ($currencies as $code => $meta)
                        <option value="{{ $code }}">{{ $code }}</option>
                    @endforeach
                </select>
                @error('currency') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="field-label">Entity (optional)</label>
            <select wire:model="billing_entity_id" class="field">
                <option value="">All entities</option>
                @foreach ($entities as $entity)
                    <option value="{{ $entity->id }}">{{ $entity->name }}</option>
                @endforeach
            </select>
            @error('billing_entity_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="is_active"> Active
        </label>
        <div class="flex gap-2">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('services.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
