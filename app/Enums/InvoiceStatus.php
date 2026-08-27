<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Overdue = 'overdue';
    case AwaitingVerification = 'awaiting_verification';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Void = 'void';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Unpaid',
            self::Overdue => 'Overdue',
            self::AwaitingVerification => 'Awaiting verification',
            self::Paid => 'Paid',
            self::PartiallyPaid => 'Partial',
            self::Void => 'Void',
            self::Refunded => 'Refunded',
        };
    }

    public function background(): string
    {
        return match ($this) {
            self::Paid => '#DCFCE7',
            self::Overdue => '#FEE2E2',
            self::Draft => '#F4F4F5',
            self::Void, self::Refunded => '#F4F4F5',
            self::PartiallyPaid => '#DBEAFE',
            self::AwaitingVerification => '#E0E7FF',
            self::Sent => '#FEF3C7',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid => '#15803D',
            self::Overdue => '#B91C1C',
            self::Draft => '#52525B',
            self::Void, self::Refunded => '#71717A',
            self::PartiallyPaid => '#1D4ED8',
            self::AwaitingVerification => '#4338CA',
            self::Sent => '#B45309',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Sent, self::Overdue, self::PartiallyPaid, self::AwaitingVerification], true);
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Overdue, self::AwaitingVerification], true);
    }

    public function isPayable(): bool
    {
        return $this->isOpen();
    }
}
