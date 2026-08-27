<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Support\Permissions;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::SERVICES_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::SERVICES_CREATE);
    }

    public function update(User $user, Service $service): bool
    {
        return $user->can(Permissions::SERVICES_UPDATE);
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->can(Permissions::SERVICES_DELETE);
    }
}
