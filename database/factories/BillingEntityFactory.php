<?php

namespace Database\Factories;

use App\Models\BillingEntity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BillingEntity>
 */
class BillingEntityFactory extends Factory
{
    protected $model = BillingEntity::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        $data = [
            'name' => $name,
            'legal_name' => $name.' Ltd',
            'slug' => Str::slug($name),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'city' => fake()->city(),
            'postcode' => fake()->postcode(),
            'country' => 'United Kingdom',
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'vat_number' => 'GB'.fake()->numerify('#########'),
            'vat_registered' => true,
            'invoice_prefix' => strtoupper(fake()->lexify('??')),
            'next_invoice_number' => 1,
            'numbering_year' => now()->year,
            'default_currency' => 'GBP',
            'default_vat_rate' => 20,
            'default_due_days' => 14,
            'bank_name' => 'Starling Bank',
            'account_name' => $name.' Ltd',
            'sort_code' => '04-00-04',
            'account_number' => fake()->numerify('########'),
            'iban' => null,
            'bic' => null,
            'invoice_footer' => 'Thank you for your business.',
            'terms' => 'Payment is due within 14 days.',
            'is_active' => true,
        ];

        return [
            ...$data,
            ...BillingEntity::composeLegacyDetails($data),
        ];
    }
}
