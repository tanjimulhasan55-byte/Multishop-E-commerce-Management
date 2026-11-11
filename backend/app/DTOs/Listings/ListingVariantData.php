<?php

namespace App\DTOs\Listings;

final readonly class ListingVariantData
{
    public function __construct(
        public int $variantId,
        public string $price,
        public ?string $compareAtPrice,
        public int $stock,
        public bool $isActive,
    ) {}

    public static function fromArray(array $v): self
    {
        return new self(
            variantId: (int) $v['variant_id'],
            price: ListingData::money($v['price']),
            compareAtPrice: ListingData::money($v['compare_at_price'] ?? null),
            stock: (int) $v['stock'],
            isActive: (bool) ($v['is_active'] ?? true),
        );
    }

    public function toAttributes(): array
    {
        return [
            'price' => $this->price,
            'compare_at_price' => $this->compareAtPrice,
            'stock' => $this->stock,
            'is_active' => $this->isActive,
        ];
    }
}
