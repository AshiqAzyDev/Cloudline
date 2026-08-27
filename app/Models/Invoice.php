<?php

namespace App\Models;

use App\Enums\InvoiceEventType;
use App\Enums\InvoiceStatus;
use App\Enums\VatTreatment;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'billing_entity_id',
        'client_id',
        'created_by',
        'currency',
        'status',
        'issue_date',
        'due_date',
        'subtotal_minor',
        'vat_enabled',
        'vat_rate',
        'vat_minor',
        'total_minor',
        'amount_paid_minor',
        'vat_treatment',
        'notes',
        'terms',
        'pay_token',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'revision',
        'fx_rate_to_gbp',
        'total_gbp_minor',
        'sent_at',
        'paid_at',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'vat_treatment' => VatTreatment::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal_minor' => 'integer',
            'vat_enabled' => 'boolean',
            'vat_rate' => 'decimal:2',
            'vat_minor' => 'integer',
            'total_minor' => 'integer',
            'amount_paid_minor' => 'integer',
            'revision' => 'integer',
            'fx_rate_to_gbp' => 'decimal:6',
            'total_gbp_minor' => 'integer',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (! $invoice->pay_token) {
                $invoice->pay_token = (string) Str::ulid();
            }
        });
    }

    public function billingEntity(): BelongsTo
    {
        return $this->belongsTo(BillingEntity::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(InvoiceEvent::class)->latest();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class)->orderBy('scheduled_for');
    }

    public function displayNumber(): string
    {
        return $this->number ?: 'Draft';
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total_minor, $this->currency);
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal_minor, $this->currency);
    }

    public function formattedVat(): string
    {
        return Money::format($this->vat_minor, $this->currency);
    }

    public function outstandingMinor(): int
    {
        return max(0, $this->total_minor - $this->amount_paid_minor);
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isPayable(): bool
    {
        return $this->status->isPayable() && $this->outstandingMinor() > 0;
    }

    public function recordEvent(InvoiceEventType $type, array $payload = [], ?User $user = null): InvoiceEvent
    {
        return $this->events()->create([
            'type' => $type->value,
            'payload' => $payload ?: null,
            'user_id' => $user?->id ?? auth()->id(),
        ]);
    }
}
