<?php

namespace App\Actions\Payments;

use App\Contracts\PaymentGateway;
use App\DTOs\Payments\PaymentInitiation;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InitiatePaymentAction
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function execute(Order $order): PaymentInitiation
    {
        if (! $this->gateway->isEnabled()) {
            throw ValidationException::withMessages(['payment' => __('Online payment is not available right now.')]);
        }

        if ($order->payment_method !== PaymentMethod::SslCommerz
            || $order->isPaid()
            || $order->status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages(['payment' => __('This order cannot be paid online.')]);
        }

        $payment = DB::transaction(function () use ($order) {
            $order->update(['payment_status' => PaymentStatus::Pending]);

            return $order->payments()->create([
                'gateway' => $this->gateway->name(),
                'transaction_id' => $order->number.'-'.Str::upper(Str::random(8)),
                'amount' => $order->total,
                'currency' => $order->currency,
                'status' => PaymentStatus::Pending,
            ]);
        });

        return $this->gateway->initiate($order, $payment);
    }
}
