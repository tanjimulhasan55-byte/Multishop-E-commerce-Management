<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Shop Owner
 */
class ListShopListingsController extends Controller
{
    use Paginates;

    /** All listings of one of your shops, including inactive ones. */
    public function __invoke(Request $request, Shop $shop): AnonymousResourceCollection
    {
        $this->authorize('manageListings', $shop);

        $listings = QueryBuilder::for(
            $shop->listings()->getQuery()->with(ShopProduct::displayRelations())
        )
            ->allowedFilters(
                AllowedFilter::exact('is_active'),
                AllowedFilter::callback('search', fn (Builder $q, $v) => $q->whereHas('product', fn (Builder $p) => $p->search((string) $v))),
                AllowedFilter::callback('low_stock', fn (Builder $q, $v) => filter_var($v, FILTER_VALIDATE_BOOL) ? $q->where('stock', '<', 5) : $q),
            )
            ->allowedSorts('price', 'stock', 'created_at')
            ->allowedIncludes('product')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ListingResource::collection($listings);
    }
}
