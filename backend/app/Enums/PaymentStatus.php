<?php

namespace App\Enums;

enum PaymentStatus: string
{
    use HasLabel;

    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'emerald',
            self::Pending => 'amber',
            self::Unpaid => 'slate',
            self::Failed, self::Cancelled => 'rose',
            self::Refunded => 'violet',
        };
    }
}
