<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ValidationMessages
{
    /**
     * @return array<string, string>
     */
    public static function invoiceAttributes(): array
    {
        return [
            'billing_entity_id' => 'entity',
            'client_id' => 'client',
            'currency' => 'currency',
            'issue_date' => 'issue date',
            'due_date' => 'due date',
            'vat_rate' => 'VAT rate',
            'vat_treatment' => 'VAT treatment',
            'items' => 'line items',
            'items.*.description' => 'description',
            'items.*.qty' => 'quantity',
            'items.*.unit_price' => 'rate',
            'quick_company' => 'company name',
            'quick_email' => 'email',
            'quick_contact' => 'contact name',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function invoiceMessages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'billing_entity_id.required' => 'Please select a billing entity.',
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
            'items.*.description.required' => 'Enter a description for each line item.',
            'items.*.qty.required' => 'Enter a quantity for each line item.',
            'items.*.qty.min' => 'Quantity must be greater than zero.',
            'items.*.unit_price.required' => 'Enter a rate for each line item.',
            'items.*.unit_price.min' => 'Rate cannot be negative.',
            'due_date.after_or_equal' => 'Due date must be on or after the issue date.',
            'quick_company.required' => 'Company name is required.',
            'quick_email.required' => 'Email is required.',
            'quick_email.email' => 'Enter a valid email address.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function clientAttributes(): array
    {
        return [
            'company' => 'company name',
            'contact' => 'contact name',
            'email' => 'email',
            'phone' => 'phone',
            'address' => 'address',
            'country' => 'country',
            'vat_number' => 'VAT number',
            'default_currency' => 'currency',
            'vat_treatment' => 'VAT treatment',
            'notes' => 'notes',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function serviceAttributes(): array
    {
        return [
            'name' => 'service name',
            'description' => 'description',
            'default_rate' => 'default rate',
            'currency' => 'currency',
            'billing_entity_id' => 'entity',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function currencyRule(): array
    {
        return ['required', 'string', 'size:3', Rule::in(CurrencyCatalog::codes())];
    }
}
