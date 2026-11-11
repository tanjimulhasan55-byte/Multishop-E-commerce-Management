<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Shop Owner
 */
class DashboardController extends Controller
{
    /** Headline numbers across all of the owner's shops. */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopIds = Shop::query()->ownedBy($user)->pluck('id');

        $orders = Order::query()->whereIn('shop_id', $shopIds);

        return response()->json(['meta' => [
            'shops' => $shopIds->count(),
            'listings' => ShopProduct::query()->whereIn('shop_id', $shopIds)->count(),
            'active_listings' => ShopProduct::query()->whereIn('shop_id', $shopIds)->where('is_active', true)->count(),
            'low_stock' => ShopProduct::query()->whereIn('shop_id', $shopIds)->where('stock', '<', 5)->count(),
            'my_products' => Product::query()->where('created_by', $user->getKey())->count(),
            'orders' => (clone $orders)->count(),
            'pending_orders' => (clone $orders)->where('status', OrderStatus::Pending->value)->count(),
            'revenue' => number_format((float) (clone $orders)->where('payment_status', PaymentStatus::Paid->value)->sum('total'), 2, '.', ''),
        ]]);
    }
}
