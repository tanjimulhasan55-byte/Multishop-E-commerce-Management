<?php

namespace App\Http\Controllers\Api\V1\Admin\Products;

use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Admin
 */
class IndexProductsController extends Controller
{
    use Paginates;

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $products = QueryBuilder::for(Product::query()->with(['media', 'category'])->withCount('listings'))
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category', 'category_id'),
                AllowedFilter::exact('created_by'),
                AllowedFilter::callback('search', fn (Builder $q, $v) => $q->search((string) $v)),
                AllowedFilter::callback('unassigned', fn (Builder $q, $v) => filter_var($v, FILTER_VALIDATE_BOOL) ? $q->doesntHave('listings') : $q),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name_en', 'base_price', 'created_at', 'listings_count')
            ->allowedIncludes('category', 'creator')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ProductResource::collection($products);
    }
}
