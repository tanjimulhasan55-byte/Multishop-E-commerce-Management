<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Account
 */
class ListOrdersController extends Controller
{
    use Paginates;

    /** The authenticated customer's own orders. */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $orders = QueryBuilder::for(Order::query()->whereBelongsTo($request->user(), 'customer')->with(['items', 'shop']))
            ->allowedFilters(AllowedFilter::exact('status'), AllowedFilter::exact('payment_status'))
            ->allowedSorts('placed_at', 'total')
            ->defaultSort('-placed_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return OrderResource::collection($orders);
    }
}
