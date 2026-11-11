<?php

namespace App\DTOs\Listings;

use App\DTOs\Concerns\CleansTranslations;
use Illuminate\Http\UploadedFile;

/**
 * A shop's own details, thumbnail and per-variant price/stock for a
 * catalog product.
 *
 * `price` / `compareAtPrice` / `stock` are shop-wide defaults applied to
 * every variant (when given); `variants` holds per-variant overrides keyed
 * by product_variant_id.
 */
final readonly class ListingData
{
    use CleansTranslations;

    /**
     * @param  array<string, string>|null  $title
     * @param  array<string, string>|null  $description
     * @param  array<int, ListingVariantData>  $variants  keyed by product_variant_id
     */
    public function __construct(
        public ?string $price,
        public ?string $compareAtPrice,
        public ?int $stock,
        public ?array $title,
        public ?array $description,
        public bool $isActive,
        public array $variants = [],
        public ?UploadedFile $thumbnail = null,
        public bool $removeThumbnail = false,
    ) {}

    public static function fromArray(array $data): self
    {
        $variants = collect($data['variants'] ?? [])
            ->mapWithKeys(fn (array $v) => [(int) $v['variant_id'] => ListingVariantData::fromArray($v)])
            ->all();

        return new self(
            price: self::money($data['price'] ?? null),
            compareAtPrice: self::money($data['compare_at_price'] ?? null),
            stock: isset($data['stock']) && $data['stock'] !== '' ? (int) $data['stock'] : null,
            title: self::translations($data['title'] ?? null),
            description: self::translations($data['description'] ?? null),
            isActive: (bool) ($data['is_active'] ?? true),
            variants: $variants,
            thumbnail: $data['thumbnail'] ?? null,
            removeThumbnail: (bool) ($data['remove_thumbnail'] ?? false),
        );
    }

    public static function money(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : number_format((float) $value, 2, '.', '');
    }

    /** Listing-level columns (aggregates are maintained separately). */
    public function toAttributes(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}
