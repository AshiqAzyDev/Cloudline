<?php

namespace Database\Factories;

use App\Models\Service;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'billing_entity_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'default_rate_minor' => Money::toMinor(fake()->numberBetween(500, 5000), 'GBP'),
            'currency' => 'GBP',
            'is_active' => true,
        ];
    }
}
