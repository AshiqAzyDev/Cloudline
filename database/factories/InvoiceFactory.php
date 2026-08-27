<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\VatTreatment;
use App\Models\BillingEntity;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = 100000;
        $vat = 20000;

        return [
            'number' => null,
            'billing_entity_id' => BillingEntity::factory(),
            'client_id' => Client::factory(),
            'currency' => 'GBP',
            'status' => InvoiceStatus::Draft,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'subtotal_minor' => $subtotal,
            'vat_enabled' => true,
            'vat_rate' => 20,
            'vat_minor' => $vat,
            'total_minor' => $subtotal + $vat,
            'amount_paid_minor' => 0,
            'vat_treatment' => VatTreatment::Standard,
            'pay_token' => (string) Str::ulid(),
            'revision' => 1,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Sent,
            'number' => 'CT-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'sent_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->sent()->state(fn () => [
            'status' => InvoiceStatus::Overdue,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);
    }

    public function paid(): static
    {
        return $this->sent()->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'amount_paid_minor' => $attributes['total_minor'],
            'paid_at' => now(),
        ]);
    }
}
