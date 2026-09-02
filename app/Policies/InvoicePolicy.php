<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Support\Permissions;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::INVOICES_VIEW) || $user->can(Permissions::PORTAL_VIEW);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isClient()) {
            return $user->client_id === $invoice->client_id;
        }

        return $user->can(Permissions::INVOICES_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::INVOICES_CREATE);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if (! $user->can(Permissions::INVOICES_UPDATE)) {
            return false;
        }

        return $invoice->isEditable() || $invoice->status === InvoiceStatus::Paid;
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->can(Permissions::INVOICES_SEND);
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $user->can(Permissions::INVOICES_VOID);
    }
}
