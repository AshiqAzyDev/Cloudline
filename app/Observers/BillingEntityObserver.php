<?php

namespace App\Observers;

use App\Models\BillingEntity;

class BillingEntityObserver
{
    public function saving(BillingEntity $entity): void
    {
        $composed = BillingEntity::composeLegacyDetails($entity->only([
            'address_line1',
            'address_line2',
            'city',
            'postcode',
            'country',
            'bank_name',
            'account_name',
            'sort_code',
            'account_number',
            'iban',
            'bic',
        ]));

        $entity->address = $composed['address'];
        $entity->bank_details = $composed['bank_details'];
    }
}
