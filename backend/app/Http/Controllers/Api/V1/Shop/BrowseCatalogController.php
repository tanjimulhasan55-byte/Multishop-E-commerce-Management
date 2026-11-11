<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Enums\ProductStatus;
use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Shop Owner
 */
class BrowseCatalogController extends Controller
{
    use Paginates;

    /**
     * The shared catalog: every active product (created by admins or any
     * shop owner) plus the caller's own drafts.
     *
     * Pass `?shop=<slug>` (one of your shops) to get `listed_in_shop` per product.
     * Filters: `filter[search]`, `filter[category]` (id), `filter[mine]=1`,
     * `filter[status]`, `filter[not_listed]=1` (requires `shop`).
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $user = $request->user();

        $shop = $request->filled('shop')
            ? Shop::query()->ownedBy($user)->where('slug', (string) $request->query('shop'))->firstOrFail()
            : null;

        $base = Product::query()
            ->where(fn (Builder $q) => $q->where('status', ProductStatus::Active->value)
                ->orWhere('created_by', $user->getKey()))
            ->with(Product::detailRelations())
            ->withCount('listings')
            ->when($shop, fn (Builder $q) => $q->withExists(['listings as listed_in_shop' => fn ($l) => $l->where('shop_id', $shop->getKey())]));

        $products = QueryBuilder::for($base)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $q, $v) => $q->search((string) $v)),
                AllowedFilter::exact('category', 'category_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('mine', fn (Builder $q, $v) => filter_var($v, FILTER_VALIDATE_BOOL) ? $q->where('created_by', $user->getKey()) : $q),
                AllowedFilter::callback('not_listed', fn (Builder $q, $v) => $shop && filter_var($v, FILTER_VALIDATE_BOOL)
                    ? $q->whereDoesntHave('listings', fn ($l) => $l->where('shop_id', $shop->getKey()))
                    : $q),
            )
            ->allowedSorts('name_en', 'base_price', 'created_at')
            ->allowedIncludes('category', 'creator')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request, 12))
            ->withQueryString();

        return ProductResource::collection($products);
    }
}
