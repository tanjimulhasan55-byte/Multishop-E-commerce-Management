<?php

namespace App\DTOs\Orders;

final readonly class CheckoutItemData
{
    /** @param int $variantId a shop-variant id (listing `variants[].id`) */
    public function __construct(
        public int $variantId,
        public int $quantity,
    ) {}
}
