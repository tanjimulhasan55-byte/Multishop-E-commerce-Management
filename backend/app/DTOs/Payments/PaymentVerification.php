<?php

namespace App\DTOs\Payments;

final readonly class PaymentVerification
{
    public function __construct(
        public bool $successful,
        public string $transactionId,
        public ?string $reference,
        public ?string $amount,
        public ?string $currency,
        public array $raw = [],
    ) {}
}
