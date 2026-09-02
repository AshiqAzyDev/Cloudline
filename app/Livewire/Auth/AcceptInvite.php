<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Accept invite')]
class AcceptInvite extends Component
{
    #[Locked]
    public int $userId;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        if ($user->email_verified_at) {
            abort(403, 'This invite has already been used. Please log in instead.');
        }

        if (! $user->is_active) {
            abort(403, 'This account is inactive. Contact your administrator.');
        }

        if ($user->isClient()) {
            abort(403, 'The client portal is not available. Please use the payment link from your invoice email.');
        }

        $this->userId = $user->id;
    }

    public function accept()
    {
        $this->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required' => 'Please choose a password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = User::query()->findOrFail($this->userId);

        if ($user->email_verified_at) {
            session()->flash('error', 'This invite has already been used. Please log in instead.');

            return redirect()->route('login');
        }

        if ($user->isClient()) {
            session()->flash('error', 'The client portal is not available. Please use the payment link from your invoice email.');

            return redirect()->route('login');
        }

        $user->forceFill([
            'password' => $this->password,
            'email_verified_at' => now(),
            'is_active' => true,
        ])->save();

        Auth::login($user);
        session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.accept-invite');
    }
}
