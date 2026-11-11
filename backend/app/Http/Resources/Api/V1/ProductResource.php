<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/**
 * A shared-catalog product (shop owner / admin views only — customers see
 * ListingResource instead).
 *
 * @mixin Product
 */
class ProductResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'products';
    }

    public function toAttributes(Request $request): array
    {
        $user = $request->user();

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'status' => $this->status->value,
            'category_id' => $this->category_id,
            'created_by' => $this->created_by,
            'is_mine' => $user !== null && (int) $this->created_by === (int) $user->getKey(),
            'can' => $user ? [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ] : null,
            'thumbnail' => $this->thumbnailUrls(),
            'category_path' => $this->whenLoaded('category', fn () => $this->category?->path),
            'options' => $this->whenLoaded('variants', fn () => $this->optionMatrix()),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($v) => [
                'id' => $v->getKey(),
                'sku' => $v->sku,
                'label' => $v->label(),
                'is_default' => $v->isDefault(),
                'base_price' => $v->base_price,
                'option_value_ids' => $v->attributeValues->pluck('id')->all(),
            ])->all()),
            'shops_count' => $this->whenCounted('listings'),
            // Set when the catalog is browsed for a specific shop (?shop=slug).
            'listed_in_shop' => $this->whenHas('listed_in_shop', fn () => (bool) $this->listed_in_shop),
            'translations' => [
                'name' => (object) $this->getTranslations('name'),
                'description' => (object) $this->getTranslations('description'),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'category' => CategoryResource::class,
            'creator' => UserResource::class,
        ];
    }
}
