<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permissions::labels() as $name => $label) {
            Permission::findOrCreate($name);
        }

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions(Permissions::all());

        $staff = Role::findOrCreate('staff');
        $staff->syncPermissions(Permissions::staffDefaults());

        $client = Role::findOrCreate('client');
        $client->syncPermissions([Permissions::PORTAL_VIEW]);
    }
}
