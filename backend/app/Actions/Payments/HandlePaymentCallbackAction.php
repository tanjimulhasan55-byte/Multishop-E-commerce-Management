<?php

namespace App\Actions\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles success / IPN / fail / cancel callbacks. Idempotent: the payment
 * row is locked and a payment that is already paid is never changed again.
 */
final class HandlePaymentCallbackAction
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * @param  'success'|'fail'|'cancel'  $outcome  what the gateway claims happened
     */
    public function execute(array $payload, string $outcome): ?Payment
    {
        $tranId = (string) ($payload['tran_id'] ?? '');

        if ($tranId === '') {
            return null;
        }

        return DB::transaction(function () use ($payload, $outcome, $tranId) {
            $payment = Payment::query()->where('transaction_id', $tranId)->lockForUpdate()->first();

            if ($payment === null || $payment->status === PaymentStatus::Paid) {
                return $payment;
            }

            $order = $payment->order()->lockForUpdate()->first();

            if ($outcome !== 'success') {
                $status = $outcome === 'cancel' ? PaymentStatus::Cancelled : PaymentStatus::Failed;
                $payment->update(['status' => $status, 'payload' => $payload]);
                $order->update(['payment_status' => $status]);

                return $payment;
            }

            // Server-to-server validation; the browser-posted body is untrusted.
            $verification = $this->gateway->verify($payload);

            $amountMatches = $verification->amount !== null
                && abs((float) $verification->amount - (float) $payment->amount) < 0.01
                && strtoupper((string) $verification->currency) === strtoupper($payment->currency);

            if (! $verification->successful || ! $amountMatches) {
                Log::warning('Payment verification failed', ['transaction_id' => $tranId]);
                $payment->update(['status' => PaymentStatus::Failed, 'payload' => $verification->raw]);
                $order->update(['payment_status' => PaymentStatus::Failed]);

                return $payment;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'gateway_reference' => $verification->reference,
                'payload' => $verification->raw,
                'paid_at' => now(),
            ]);

            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => $order->status === OrderStatus::Pending ? OrderStatus::Processing : $order->status,
            ]);

            return $payment;
        });
    }
}
