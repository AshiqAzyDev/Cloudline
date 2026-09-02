<?php

namespace Tests\Feature;

use App\Livewire\Auth\AcceptInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcceptInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_invite_is_rejected_before_credentials_are_saved(): void
    {
        $user = $this->clientUser();
        $user->forceFill([
            'email_verified_at' => null,
            'password' => 'existing-password-hash',
            'is_active' => true,
        ])->save();

        $passwordHashBefore = $user->fresh()->password;

        Livewire::test(AcceptInviteWithoutMountGuards::class, ['user' => $user])
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('accept')
            ->assertRedirect(route('login'));

        $user->refresh();

        $this->assertNull($user->email_verified_at);
        $this->assertSame($passwordHashBefore, $user->password);
    }

    public function test_client_cannot_open_invite_page(): void
    {
        $user = $this->clientUser();
        $user->forceFill(['email_verified_at' => null, 'is_active' => true])->save();

        Livewire::test(AcceptInvite::class, ['user' => $user])
            ->assertForbidden();
    }

    public function test_staff_can_accept_invite_and_is_logged_in(): void
    {
        $this->seedRoles();
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_active' => true,
        ]);
        $user->assignRole('staff');

        Livewire::test(AcceptInvite::class, ['user' => $user])
            ->set('password', 'secure-password')
            ->set('password_confirmation', 'secure-password')
            ->call('accept')
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }
}

/** @internal */
class AcceptInviteWithoutMountGuards extends AcceptInvite
{
    public function mount(User $user): void
    {
        $this->userId = $user->id;
    }
}
