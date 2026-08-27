<?php

namespace Tests;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    protected function admin(array $overrides = []): User
    {
        $this->seedRoles();
        $user = User::factory()->create($overrides);
        $user->assignRole('admin');

        return $user;
    }

    protected function staff(array $overrides = []): User
    {
        $this->seedRoles();
        $user = User::factory()->create($overrides);
        $user->assignRole('staff');

        return $user;
    }

    protected function clientUser(?Client $client = null, array $overrides = []): User
    {
        $this->seedRoles();
        $client ??= Client::factory()->create();
        $user = User::factory()->create(array_merge(['client_id' => $client->id], $overrides));
        $user->assignRole('client');

        return $user;
    }
}
