<?php

namespace Database\Factories;

use App\Enums\VatTreatment;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'company' => fake()->company(),
            'contact' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'country' => 'GB',
            'vat_number' => null,
            'default_currency' => 'GBP',
            'vat_treatment' => VatTreatment::Standard,
            'notes' => null,
        ];
    }
}
