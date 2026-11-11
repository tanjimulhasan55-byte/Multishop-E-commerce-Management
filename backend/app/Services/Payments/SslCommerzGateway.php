<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\DTOs\Payments\PaymentInitiation;
use App\DTOs\Payments\PaymentVerification;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * SSLCommerz hosted checkout (v4).
 *
 * @see https://developer.sslcommerz.com/doc/v4/
 */
final class SslCommerzGateway implements PaymentGateway
{
    public function __construct(
        private readonly ?string $storeId,
        #[\SensitiveParameter] private readonly ?string $storePassword,
        private readonly bool $sandbox,
        private readonly int $timeout = 30,
    ) {}

    public function name(): string
    {
        return 'sslcommerz';
    }

    public function isEnabled(): bool
    {
        return filled($this->storeId) && filled($this->storePassword);
    }

    private function baseUrl(): string
    {
        return $this->sandbox ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';
    }

    public function initiate(Order $order, Payment $payment): PaymentInitiation
    {
        $this->ensureEnabled();

        $order->loadMissing(['items', 'customer']);

        $response = Http::asForm()
            ->timeout($this->timeout)
            ->post($this->baseUrl().'/gwprocess/v4/api.php', [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'total_amount' => $payment->amount,
                'currency' => $payment->currency,
                'tran_id' => $payment->transaction_id,
                'success_url' => route('api.v1.payments.sslcommerz.success'),
                'fail_url' => route('api.v1.payments.sslcommerz.fail'),
                'cancel_url' => route('api.v1.payments.sslcommerz.cancel'),
                'ipn_url' => route('api.v1.payments.sslcommerz.ipn'),
                'cus_name' => $order->customer_name,
                'cus_email' => $order->customer?->email ?? 'customer@multishop.com',
                'cus_phone' => $order->customer_phone,
                'cus_add1' => $order->shipping_address,
                'cus_city' => 'Dhaka',
                'cus_country' => 'Bangladesh',
                'shipping_method' => 'NO',
                'num_of_item' => $order->items->sum('quantity'),
                'product_name' => mb_substr($order->items->pluck('product_name')->implode(', '), 0, 250),
                'product_category' => 'General',
                'product_profile' => 'general',
                'value_a' => $order->number,
            ]);

        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['status'] ?? null) !== 'SUCCESS' || empty($body['GatewayPageURL'])) {
            throw new RuntimeException('SSLCommerz session could not be created: '.($body['failedreason'] ?? 'unknown error'));
        }

        return new PaymentInitiation($body['GatewayPageURL'], $payment->transaction_id);
    }

    public function verify(array $payload): PaymentVerification
    {
        $this->ensureEnabled();

        $tranId = (string) ($payload['tran_id'] ?? '');
        $valId = (string) ($payload['val_id'] ?? '');

        if ($tranId === '' || $valId === '') {
            return new PaymentVerification(false, $tranId, null, null, null, $payload);
        }

        $response = Http::timeout($this->timeout)->get($this->baseUrl().'/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'format' => 'json',
        ]);

        $body = $response->json() ?? [];
        $valid = $response->successful()
            && in_array($body['status'] ?? null, ['VALID', 'VALIDATED'], true)
            && hash_equals($tranId, (string) ($body['tran_id'] ?? ''));

        return new PaymentVerification(
            successful: $valid,
            transactionId: $tranId,
            reference: $body['bank_tran_id'] ?? $valId,
            amount: isset($body['currency_amount']) ? (string) $body['currency_amount'] : ($body['amount'] ?? null),
            currency: $body['currency_type'] ?? $body['currency'] ?? null,
            raw: $body,
        );
    }

    private function ensureEnabled(): void
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('SSLCommerz is not configured.');
        }
    }
}
