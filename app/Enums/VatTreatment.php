<?php

namespace App\Enums;

enum VatTreatment: string
{
    case Standard = 'standard';
    case ZeroRated = 'zero_rated';
    case ReverseCharge = 'reverse_charge';
    case OutOfScope = 'out_of_scope';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard UK VAT',
            self::ZeroRated => 'Zero-rated',
            self::ReverseCharge => 'Reverse charge',
            self::OutOfScope => 'Out of scope',
        };
    }

    public function invoiceNote(): ?string
    {
        return match ($this) {
            self::Standard => null,
            self::ZeroRated => 'Zero-rated supply.',
            self::ReverseCharge => 'Reverse charge: customer to account for VAT.',
            self::OutOfScope => 'Services outside the scope of UK VAT.',
        };
    }

    public function appliesVat(): bool
    {
        return $this === self::Standard;
    }
}
