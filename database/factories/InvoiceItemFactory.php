<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $qty = 1;
        $rate = 100000;

        return [
            'invoice_id' => Invoice::factory(),
            'service_id' => null,
            'description' => fake()->sentence(3),
            'qty' => $qty,
            'unit_price_minor' => $rate,
            'amount_minor' => $qty * $rate,
            'sort_order' => 0,
        ];
    }
}
