<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReminderRule extends Model
{
    protected $fillable = [
        'billing_entity_id',
        'offset_days',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'offset_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function billingEntity(): BelongsTo
    {
        return $this->belongsTo(BillingEntity::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }
}
