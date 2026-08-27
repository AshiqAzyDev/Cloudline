<div>
    <h1 class="font-display text-xl font-semibold tracking-tight">Reset password</h1>
    <p class="mb-4 mt-1 text-[13px] text-muted">We’ll email you a reset link.</p>

    @if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT)
        <p class="mb-3 text-[13px] font-medium text-green-700">{{ __($status) }}</p>
    @endif

    <form wire:submit="send" class="space-y-3">
        <div>
            <label class="field-label">Email</label>
            <input type="email" wire:model="email" class="field">
            @error('email') <p class="mt-1 text-[12px] text-red-600">{{ $message }}</p> @enderror
        </div>
        <button class="btn btn-primary w-full !py-2">Send reset link</button>
        <a href="{{ route('login') }}" class="block text-center text-[12.5px] font-semibold text-accent">Back to login</a>
    </form>
</div>
