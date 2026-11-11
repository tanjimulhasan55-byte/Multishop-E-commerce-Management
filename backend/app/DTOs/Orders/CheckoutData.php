<?php

namespace App\DTOs\Orders;

use App\Enums\PaymentMethod;

final readonly class CheckoutData
{
    /**
     * @param  list<CheckoutItemData>  $items
     */
    public function __construct(
        public int $shopId,
        public array $items,
        public string $customerName,
        public string $customerPhone,
        public string $shippingAddress,
        public ?string $notes,
        public PaymentMethod $paymentMethod,
    ) {}

    public static function fromArray(array $data): self
    {
        // Merge duplicate lines for the same variant.
        $items = collect($data['items'])
            ->groupBy(fn ($i) => (int) $i['variant_id'])
            ->map(fn ($lines, $id) => new CheckoutItemData((int) $id, (int) $lines->sum('quantity')))
            ->values()
            ->all();

        return new self(
            shopId: (int) $data['shop_id'],
            items: $items,
            customerName: trim($data['customer_name']),
            customerPhone: trim($data['customer_phone']),
            shippingAddress: trim($data['shipping_address']),
            notes: filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            paymentMethod: PaymentMethod::from($data['payment_method']),
        );
    }
}
