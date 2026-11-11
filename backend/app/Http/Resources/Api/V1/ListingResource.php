<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * A product as offered by one shop: shop-specific title, description and
 * thumbnail (falling back to the catalog product), with per-variant price
 * and stock. `price` is the lowest active variant price ("from" price).
 *
 * @mixin ShopProduct
 */
class ListingResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'listings';
    }

    public function toAttributes(Request $request): array
    {
        // Raw per-locale overrides and inactive variants are only for the owner's edit screens.
        $ownerView = $request->routeIs('api.v1.shop.*');

        return [
            'title' => $this->displayName(),
            'description' => $this->displayDescription(),
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'stock' => $this->stock,
            'in_stock' => $this->stock > 0,
            'is_active' => $this->is_active,
            'images' => $this->displayImages(),
            'has_own_thumbnail' => $this->when($ownerView, fn () => $this->hasMedia('thumbnail')),
            'shop_id' => $this->shop_id,
            'product_id' => $this->product_id,
            'sku' => $this->product?->sku,
            'category' => $this->product?->category?->name,
            'has_options' => $this->whenLoaded('variants', fn () => $this->variants->contains(fn ($v) => ! $v->variant?->isDefault())),
            'options' => $this->whenLoaded('variants', fn () => $this->optionMatrix($ownerView)),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants
                ->filter(fn (ShopProductVariant $v) => $ownerView || $v->is_active)
                ->sortBy(fn (ShopProductVariant $v) => [$v->variant?->position, $v->id])
                ->map(fn (ShopProductVariant $v) => [
                    'id' => $v->getKey(),
                    'variant_id' => $v->product_variant_id,
                    'sku' => $v->variant?->sku,
                    'label' => $v->variant?->label(),
                    'option_value_ids' => $v->variant?->attributeValues->pluck('id')->all() ?? [],
                    'price' => $v->price,
                    'compare_at_price' => $v->compare_at_price,
                    'stock' => $v->stock,
                    'in_stock' => $v->stock > 0,
                    'is_active' => $v->is_active,
                ])->values()->all()),
            'overrides' => $this->when($ownerView, fn () => [
                'title' => (object) $this->getTranslations('title'),
                'description' => (object) $this->getTranslations('description'),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /** Attribute → values actually offered by this listing (for the picker). */
    private function optionMatrix(bool $includeInactive): array
    {
        return $this->variants
            ->filter(fn (ShopProductVariant $v) => $includeInactive || $v->is_active)
            ->flatMap(fn (ShopProductVariant $v) => $v->variant?->attributeValues ?? [])
            ->unique('id')
            ->groupBy('attribute_id')
            ->map(fn ($values) => [
                'attribute_id' => $values->first()->attribute_id,
                'name' => $values->first()->attribute?->name,
                'type' => $values->first()->attribute?->type,
                'sort_order' => $values->first()->attribute?->sort_order,
                'values' => $values->sortBy('sort_order')->map(fn ($v) => [
                    'id' => $v->getKey(),
                    'value' => $v->value,
                    'color_hex' => $v->color_hex,
                ])->values()->all(),
            ])
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    public function toRelationships(Request $request): array
    {
        return [
            'shop' => ShopResource::class,
            'product' => ProductResource::class,
        ];
    }
}
