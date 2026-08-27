<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.landing')]
#[Title('Cloudline — Sign in')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function authenticate()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password, 'is_active' => true], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 60);
            $this->addError('email', 'These credentials do not match our records, or the account is inactive.');

            return;
        }

        RateLimiter::clear($this->throttleKey());

        $user = Auth::user();

        // Client portal is deferred for v1 — block client-role logins until routes return.
        if ($user->isClient()) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            $this->addError('email', 'The client portal is not available. Please use the payment link from your invoice email.');

            return;
        }

        session()->regenerate();

        return redirect()->route('dashboard');
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Try again in {$seconds} seconds.",
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
