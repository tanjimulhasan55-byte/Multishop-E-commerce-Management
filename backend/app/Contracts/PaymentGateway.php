<?php

namespace App\Contracts;

use App\DTOs\Payments\PaymentInitiation;
use App\DTOs\Payments\PaymentVerification;
use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    /** Machine name stored on payments.gateway. */
    public function name(): string;

    /** Whether credentials are configured. */
    public function isEnabled(): bool;

    /** Create a hosted-checkout session and return where to send the customer. */
    public function initiate(Order $order, Payment $payment): PaymentInitiation;

    /**
     * Verify a callback/IPN payload server-to-server with the gateway.
     * Never trust the callback body on its own.
     */
    public function verify(array $payload): PaymentVerification;
}
