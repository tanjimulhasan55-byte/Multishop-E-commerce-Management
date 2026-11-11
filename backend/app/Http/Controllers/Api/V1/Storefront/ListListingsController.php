<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\Category;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Storefront
 */
class ListListingsController extends Controller
{
    use Paginates;

    /**
     * Products customers can buy. Only products assigned to an active shop
     * (active listing, active product) are ever returned.
     *
     * Filters: `filter[shop]=slug`, `filter[category]=slug` (includes sub-categories),
     * `filter[search]=term`, `filter[min_price]`, `filter[max_price]`, `filter[in_stock]=1`,
     * `filter[options]=valueId,valueId` (e.g. Size XL).
     * Sorts: `price`, `-price`, `created_at`, `-created_at`. Includes: `shop`, `product`.
     *
     * @unauthenticated
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $listings = QueryBuilder::for(
            ShopProduct::query()
                ->visibleToCustomers()
                ->with(ShopProduct::displayRelations())
        )
            ->allowedFilters(
                AllowedFilter::callback('shop', fn (Builder $q, $v) => $q->whereHas('shop', fn (Builder $s) => $s->where('slug', (string) $v))),
                // Category and all of its sub-categories (materialized-path prefix scan).
                AllowedFilter::callback('category', function (Builder $q, $v) {
                    $category = Category::query()->where('slug', (string) $v)->first();
                    $category
                        ? $q->whereHas('product.category', fn (Builder $c) => $c->subtreeOf($category))
                        : $q->whereRaw('1 = 0');
                }),
                // Option values, e.g. filter[options]=12,15 (XL or Red): an active, in-stock variant must match.
                AllowedFilter::callback('options', fn (Builder $q, $v) => $q->whereHas('variants', fn (Builder $sv) => $sv
                    ->where('is_active', true)->where('stock', '>', 0)
                    ->whereHas('variant.attributeValues', fn (Builder $av) => $av->whereKey(array_map('intval', (array) $v))))),
                AllowedFilter::callback('search', fn (Builder $q, $v) => $q->whereHas('product', fn (Builder $p) => $p->search((string) $v))),
                AllowedFilter::callback('min_price', fn (Builder $q, $v) => $q->where('price', '>=', (float) $v)),
                AllowedFilter::callback('max_price', fn (Builder $q, $v) => $q->where('price', '<=', (float) $v)),
                AllowedFilter::callback('in_stock', fn (Builder $q, $v) => filter_var($v, FILTER_VALIDATE_BOOL) ? $q->where('stock', '>', 0) : $q),
            )
            ->allowedSorts('price', 'created_at')
            ->allowedIncludes('shop', 'product')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request, 12))
            ->withQueryString();

        return ListingResource::collection($listings);
    }
}
