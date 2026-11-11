<?php

namespace App\Http\Controllers\Api\V1\Admin\Orders;

use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Admin
 */
class IndexOrdersController extends Controller
{
    use Paginates;

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        $orders = QueryBuilder::for(Order::query()->with(['items', 'shop']))
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('payment_status'),
                AllowedFilter::exact('shop_id'),
                AllowedFilter::exact('number'),
            )
            ->allowedSorts('placed_at', 'total')
            ->allowedIncludes('shop')
            ->defaultSort('-placed_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return OrderResource::collection($orders);
    }
}
