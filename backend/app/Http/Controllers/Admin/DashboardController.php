<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Enums\ShopStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $from = now()->subDays(13)->startOfDay();

        $daily = Order::query()
            ->where('placed_at', '>=', $from)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->selectRaw('DATE(placed_at) as day, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->day)->toDateString());

        $days = collect(range(0, 13))->map(fn ($i) => $from->copy()->addDays($i)->toDateString());

        return view('admin.dashboard', [
            'stats' => [
                'revenue' => (float) Order::query()->where('payment_status', PaymentStatus::Paid->value)->sum('total'),
                'orders' => Order::query()->count(),
                'pending' => Order::query()->where('status', OrderStatus::Pending->value)->count(),
                'shops' => Shop::query()->count(),
                'activeShops' => Shop::query()->where('status', ShopStatus::Active->value)->count(),
                'products' => Product::query()->count(),
                'unlisted' => Product::query()->doesntHave('listings')->count(),
                'owners' => User::query()->role(RoleName::ShopOwner->value)->count(),
                'customers' => User::query()->role(RoleName::Customer->value)->count(),
            ],
            'chart' => [
                'labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d M'))->all(),
                'orders' => $days->map(fn ($d) => (int) ($daily[$d]->orders ?? 0))->all(),
                'revenue' => $days->map(fn ($d) => round((float) ($daily[$d]->revenue ?? 0), 2))->all(),
            ],
            'statusBreakdown' => Order::query()->select('status', DB::raw('COUNT(*) as orders_count'))->groupBy('status')->pluck('orders_count', 'status'),
            'latestOrders' => Order::query()->with('shop')->latest('placed_at')->limit(6)->get(),
            'lowStock' => ShopProductVariant::query()
                ->where('is_active', true)->where('stock', '<', 5)
                ->with(['listing.shop', 'listing.product', 'variant.attributeValues.attribute'])
                ->orderBy('stock')->limit(6)->get(),
        ]);
    }
}
