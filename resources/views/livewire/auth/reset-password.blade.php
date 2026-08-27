<div>
    <h1 class="font-display text-xl font-semibold tracking-tight">Set a new password</h1>
    <p class="mb-4 mt-1 text-[13px] text-muted">Choose a password for your Cloudline account.</p>

    <form wire:submit="resetPassword" class="space-y-3">
        <div>
            <label class="field-label">Email</label>
            <input type="email" wire:model="email" class="field">
            @error('email') <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="field-label">Password</label>
            <input type="password" wire:model="password" class="field">
            @error('password') <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="field-label">Confirm password</label>
            <input type="password" wire:model="password_confirmation" class="field">
        </div>
        <button class="btn btn-primary w-full !py-2">Update password</button>
    </form>
</div>
