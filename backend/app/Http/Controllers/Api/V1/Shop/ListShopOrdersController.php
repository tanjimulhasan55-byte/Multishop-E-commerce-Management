<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Shop Owner
 */
class ListShopOrdersController extends Controller
{
    use Paginates;

    /** Orders placed with your shops. Filters: `filter[shop]=slug`, `filter[status]`, `filter[payment_status]`. */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $shopIds = Shop::query()->ownedBy($request->user())->pluck('id');

        $orders = QueryBuilder::for(Order::query()->whereIn('shop_id', $shopIds)->with(['items', 'shop']))
            ->allowedFilters(
                AllowedFilter::callback('shop', fn (Builder $q, $v) => $q->whereHas('shop', fn (Builder $s) => $s->where('slug', (string) $v))),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('payment_status'),
            )
            ->allowedSorts('placed_at', 'total')
            ->defaultSort('-placed_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return OrderResource::collection($orders);
    }
}
