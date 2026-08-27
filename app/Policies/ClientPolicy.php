<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Support\Permissions;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::CLIENTS_VIEW);
    }

    public function view(User $user, Client $client): bool
    {
        if ($user->isClient()) {
            return $user->client_id === $client->id;
        }

        return $user->can(Permissions::CLIENTS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::CLIENTS_CREATE);
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can(Permissions::CLIENTS_UPDATE);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->can(Permissions::CLIENTS_DELETE);
    }

    public function invite(User $user, Client $client): bool
    {
        return $user->can(Permissions::CLIENTS_INVITE);
    }
}
