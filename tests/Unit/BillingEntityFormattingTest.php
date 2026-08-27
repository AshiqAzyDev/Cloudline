<?php

namespace Tests\Unit;

use App\Models\BillingEntity;
use Tests\TestCase;

class BillingEntityFormattingTest extends TestCase
{
    public function test_structured_address_and_bank_details_are_composed_for_display(): void
    {
        $entity = new BillingEntity([
            'address_line1' => '88 Harbourfront Ave',
            'city' => 'London',
            'postcode' => 'E14 5AB',
            'country' => 'United Kingdom',
            'bank_name' => 'Starling Bank',
            'account_name' => 'Cloud Technologies Ltd',
            'sort_code' => '04-00-04',
            'account_number' => '12345678',
            'iban' => 'GB29NWBK60161331926819',
        ]);

        $this->assertSame(
            "88 Harbourfront Ave\nLondon\nE14 5AB\nUnited Kingdom",
            $entity->formattedAddress()
        );
        $this->assertStringContainsString('Sort code: 04-00-04', $entity->formattedBankDetails());
        $this->assertStringContainsString('IBAN: GB29NWBK60161331926819', $entity->formattedBankDetails());
    }

    public function test_legacy_free_text_is_used_when_structured_fields_are_empty(): void
    {
        $entity = new BillingEntity([
            'address' => "Legacy Street\nLondon",
            'bank_details' => "Sort code 11-22-33\nAccount 99999999",
        ]);

        $this->assertSame("Legacy Street\nLondon", $entity->formattedAddress());
        $this->assertSame("Sort code 11-22-33\nAccount 99999999", $entity->formattedBankDetails());
    }
}
