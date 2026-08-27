<?php

namespace App\Models;

use Database\Factories\BillingEntityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillingEntity extends Model
{
    /** @use HasFactory<BillingEntityFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'slug',
        'address',
        'address_line1',
        'address_line2',
        'city',
        'postcode',
        'country',
        'email',
        'phone',
        'vat_number',
        'vat_registered',
        'logo_path',
        'invoice_prefix',
        'next_invoice_number',
        'numbering_year',
        'default_currency',
        'default_vat_rate',
        'default_due_days',
        'bank_details',
        'bank_name',
        'account_name',
        'sort_code',
        'account_number',
        'iban',
        'bic',
        'invoice_footer',
        'terms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_invoice_number' => 'integer',
            'numbering_year' => 'integer',
            'default_vat_rate' => 'decimal:2',
            'default_due_days' => 'integer',
            'vat_registered' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'billing_entity_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'billing_entity_id');
    }

    public function reminderRules(): HasMany
    {
        return $this->hasMany(ReminderRule::class, 'billing_entity_id')->orderBy('sort_order');
    }

    public function initial(): string
    {
        return strtoupper(mb_substr($this->name, 0, 1));
    }

    public function hasStructuredAddress(): bool
    {
        return filled($this->address_line1)
            || filled($this->address_line2)
            || filled($this->city)
            || filled($this->postcode)
            || filled($this->country);
    }

    public function hasStructuredBankDetails(): bool
    {
        return filled($this->bank_name)
            || filled($this->account_name)
            || filled($this->sort_code)
            || filled($this->account_number)
            || filled($this->iban)
            || filled($this->bic);
    }

    public function formattedAddress(): string
    {
        if ($this->hasStructuredAddress()) {
            return collect([
                $this->address_line1,
                $this->address_line2,
                $this->city,
                $this->postcode,
                $this->country,
            ])->filter(fn (?string $part) => filled($part))->implode("\n");
        }

        return trim((string) $this->address);
    }

    public function formattedBankDetails(): string
    {
        if ($this->hasStructuredBankDetails()) {
            $lines = [];

            if (filled($this->bank_name)) {
                $lines[] = 'Bank: '.$this->bank_name;
            }
            if (filled($this->account_name)) {
                $lines[] = 'Account name: '.$this->account_name;
            }
            if (filled($this->sort_code)) {
                $lines[] = 'Sort code: '.$this->sort_code;
            }
            if (filled($this->account_number)) {
                $lines[] = 'Account number: '.$this->account_number;
            }
            if (filled($this->iban)) {
                $lines[] = 'IBAN: '.$this->iban;
            }
            if (filled($this->bic)) {
                $lines[] = 'BIC: '.$this->bic;
            }

            return implode("\n", $lines);
        }

        return trim((string) $this->bank_details);
    }

    /**
     * Keep legacy text columns in sync for PDF / pay page consumers.
     *
     * @param  array<string, mixed>  $structured
     * @return array{address: string|null, bank_details: string|null}
     */
    public static function composeLegacyDetails(array $structured): array
    {
        $entity = new self($structured);

        $address = $entity->formattedAddress();
        $bank = $entity->formattedBankDetails();

        return [
            'address' => $address !== '' ? $address : null,
            'bank_details' => $bank !== '' ? $bank : null,
        ];
    }
}
