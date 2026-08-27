<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    protected $fillable = [
        'invoice_id',
        'reminder_rule_id',
        'offset_days',
        'scheduled_for',
        'sent_at',
        'cancelled_at',
        'channel',
        'is_manual',
    ];

    protected function casts(): array
    {
        return [
            'offset_days' => 'integer',
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_manual' => 'boolean',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReminderRule::class, 'reminder_rule_id');
    }

    public function isPending(): bool
    {
        return $this->sent_at === null && $this->cancelled_at === null;
    }
}
