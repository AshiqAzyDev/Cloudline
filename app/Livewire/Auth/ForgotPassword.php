<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Forgot password')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    public function send(): void
    {
        $this->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Enter a valid email address.',
        ]);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->status = $status;
            $this->resetErrorBag('email');

            return;
        }

        $this->status = null;
        $this->addError('email', __($status));
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
