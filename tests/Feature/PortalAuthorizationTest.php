<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_routes_are_not_registered_in_v1(): void
    {
        $own = Client::factory()->create();
        $mine = Invoice::factory()->sent()->create(['client_id' => $own->id, 'status' => InvoiceStatus::Sent]);
        $user = $this->clientUser($own);

        $this->actingAs($user)
            ->get('/portal')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/portal/invoices/'.$mine->id)
            ->assertNotFound();
    }

    public function test_a_client_cannot_use_the_staff_app(): void
    {
        $user = $this->clientUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_cannot_open_removed_portal_paths(): void
    {
        $user = $this->staff();

        $this->actingAs($user)
            ->get('/portal')
            ->assertNotFound();
    }
}
