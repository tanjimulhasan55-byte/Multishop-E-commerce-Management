<?php

namespace App\Actions\Orders;

use App\Actions\Listings\RefreshListingAggregatesAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateOrderStatusAction
{
    public function __construct(private readonly RefreshListingAggregatesAction $refresh) {}

    public function execute(Order $order, OrderStatus $next): Order
    {
        if (! $order->status->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => __('Cannot change an order from :from to :to.', [
                    'from' => $order->status->label(),
                    'to' => $next->label(),
                ]),
            ]);
        }

        DB::transaction(function () use ($order, $next) {
            if ($next === OrderStatus::Cancelled) {
                // Return reserved stock to the shop variants that still exist.
                $items = $order->items()->whereNotNull('shop_product_variant_id')->get();

                foreach ($items as $item) {
                    ShopProductVariant::whereKey($item->shop_product_variant_id)->increment('stock', $item->quantity);
                }

                ShopProduct::query()->whereKey($items->pluck('shop_product_id')->filter()->unique())
                    ->get()->each(fn ($l) => $this->refresh->execute($l));
            }

            $order->update(['status' => $next]);
        });

        return $order;
    }
}
