<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeEvent extends Model
{
    protected $fillable = [
        'event_id',
        'type',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
