<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'billing_entity_id',
        'name',
        'description',
        'default_rate_minor',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_rate_minor' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function billingEntity(): BelongsTo
    {
        return $this->belongsTo(BillingEntity::class);
    }

    public function formattedRate(): string
    {
        return Money::format($this->default_rate_minor, $this->currency);
    }
}
