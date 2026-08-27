<?php

namespace App\Models;

use App\Enums\InvoiceEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceEvent extends Model
{
    protected $fillable = [
        'invoice_id',
        'user_id',
        'type',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceEventType::class,
            'payload' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
