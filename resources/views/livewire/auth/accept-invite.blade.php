<div>
    <h1 class="mb-1 text-xl font-bold">Set your password</h1>
    <p class="mb-6 text-sm text-muted">You've been invited to Cloudline Billing.</p>
    <form wire:submit="accept" class="space-y-4">
        <div>
            <label class="field-label">Password</label>
            <input type="password" wire:model="password" class="field">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="field-label">Confirm password</label>
            <input type="password" wire:model="password_confirmation" class="field">
        </div>
        <button class="btn btn-primary w-full">Continue</button>
    </form>
</div>
