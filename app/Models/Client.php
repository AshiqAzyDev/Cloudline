<?php

namespace App\Models;

use App\Enums\VatTreatment;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company',
        'contact',
        'email',
        'phone',
        'address',
        'country',
        'vat_number',
        'default_currency',
        'vat_treatment',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'vat_treatment' => VatTreatment::class,
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
