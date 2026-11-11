<?php

namespace App\Enums;

enum OrderStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Processing => 'sky',
            self::Shipped => 'indigo',
            self::Delivered => 'emerald',
            self::Cancelled => 'rose',
        };
    }

    /**
     * Allowed forward transitions — prevents e.g. re-opening a delivered order.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->transitions(), true);
    }
}
