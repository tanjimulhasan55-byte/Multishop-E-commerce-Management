<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShopResource;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Storefront
 */
class ListShopsController extends Controller
{
    use Paginates;

    /**
     * Active shops, with their count of customer-visible products.
     *
     * @unauthenticated
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $shops = QueryBuilder::for(
            Shop::query()->active()->with('media')
                ->withCount(['listings' => fn (Builder $q) => $q->visibleToCustomers()])
        )
            ->allowedFilters(AllowedFilter::callback('search', fn (Builder $q, $v) => $q->where('name_en', 'like', '%'.addcslashes((string) $v, '%_\\').'%')))
            ->allowedSorts('name_en', 'created_at')
            ->defaultSort('name_en')
            ->paginate($this->perPage($request, 12))
            ->withQueryString();

        return ShopResource::collection($shops);
    }
}
