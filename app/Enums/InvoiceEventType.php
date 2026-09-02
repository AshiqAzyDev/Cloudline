<?php

namespace App\Enums;

enum InvoiceEventType: string
{
    case Created = 'created';
    case Edited = 'edited';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case ReminderSent = 'reminder_sent';
    case PaymentSucceeded = 'payment_succeeded';
    case BankPaymentReported = 'bank_payment_reported';
    case MarkedPaid = 'marked_paid';
    case Voided = 'voided';
    case Duplicated = 'duplicated';
    case Refunded = 'refunded';
    case PaymentFailed = 'payment_failed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Edited => 'Edited',
            self::Sent => 'Sent to client',
            self::Viewed => 'Viewed by client',
            self::ReminderSent => 'Reminder sent',
            self::PaymentSucceeded => 'Payment received',
            self::BankPaymentReported => 'Client reported bank payment',
            self::MarkedPaid => 'Marked as paid',
            self::Voided => 'Voided',
            self::Duplicated => 'Duplicated',
            self::Refunded => 'Refunded',
            self::PaymentFailed => 'Payment failed',
        };
    }
}
