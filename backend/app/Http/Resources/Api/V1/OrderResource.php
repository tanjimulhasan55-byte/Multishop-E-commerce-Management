<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @mixin Order */
class OrderResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'orders';
    }

    public function toId(Request $request): string
    {
        return $this->number;
    }

    public function toAttributes(Request $request): array
    {
        return [
            'number' => $this->number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'payment_method' => $this->payment_method->value,
            'subtotal' => $this->subtotal,
            'shipping_total' => $this->shipping_total,
            'total' => $this->total,
            'currency' => $this->currency,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'shipping_address' => $this->shipping_address,
            'notes' => $this->notes,
            'shop_id' => $this->shop_id,
            'shop_name' => $this->whenLoaded('shop', fn () => $this->shop?->name),
            'next_statuses' => array_map(fn ($s) => $s->value, $this->status->transitions()),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'listing_id' => $item->shop_product_id,
                'variant_id' => $item->shop_product_variant_id,
                'product_name' => $item->product_name,
                'variant_label' => $item->variant_label,
                'sku' => $item->sku,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->all()),
            'placed_at' => $this->placed_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'shop' => ShopResource::class,
        ];
    }
}
