<?php

namespace App\Enums;

enum PaymentMethod: string
{
    use HasLabel;

    case CashOnDelivery = 'cod';
    case SslCommerz = 'sslcommerz';

    public function label(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Cash on Delivery',
            self::SslCommerz => 'SSLCommerz',
        };
    }
}
