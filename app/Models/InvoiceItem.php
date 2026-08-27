<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'service_id',
        'description',
        'qty',
        'unit_price_minor',
        'amount_minor',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'unit_price_minor' => 'integer',
            'amount_minor' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function formattedUnitPrice(): string
    {
        return Money::format($this->unit_price_minor, $this->invoice->currency);
    }

    public function formattedAmount(): string
    {
        return Money::format($this->amount_minor, $this->invoice->currency);
    }
}
