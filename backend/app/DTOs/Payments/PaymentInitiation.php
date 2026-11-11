<?php

namespace App\DTOs\Payments;

final readonly class PaymentInitiation
{
    public function __construct(
        public string $redirectUrl,
        public string $transactionId,
    ) {}
}
