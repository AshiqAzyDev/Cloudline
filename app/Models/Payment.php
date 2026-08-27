<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_balance_transaction_id',
        'amount_minor',
        'currency',
        'fee_minor',
        'net_minor',
        'settlement_currency',
        'settlement_amount_minor',
        'method',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'fee_minor' => 'integer',
            'net_minor' => 'integer',
            'settlement_amount_minor' => 'integer',
            'received_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
