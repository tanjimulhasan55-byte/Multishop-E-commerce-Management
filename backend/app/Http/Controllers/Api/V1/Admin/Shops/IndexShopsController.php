<?php

namespace App\Http\Controllers\Api\V1\Admin\Shops;

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
 * @tags Admin
 */
class IndexShopsController extends Controller
{
    use Paginates;

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Shop::class);

        $shops = QueryBuilder::for(Shop::query()->with('media')->withCount('listings'))
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('owner_id'),
                AllowedFilter::callback('search', fn (Builder $q, $v) => $q->where('name_en', 'like', '%'.addcslashes((string) $v, '%_\\').'%')),
                AllowedFilter::trashed(),
            )
            ->allowedSorts('name_en', 'created_at', 'listings_count')
            ->allowedIncludes('owner')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ShopResource::collection($shops);
    }
}
