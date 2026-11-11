<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShopResource;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;

/**
 * @tags Storefront
 */
class ShowShopController extends Controller
{
    /**
     * A single active shop by slug.
     *
     * @unauthenticated
     */
    public function __invoke(string $slug): ShopResource
    {
        $shop = Shop::query()->active()->where('slug', $slug)
            ->with('media')
            ->withCount(['listings' => fn (Builder $q) => $q->visibleToCustomers()])
            ->firstOrFail();

        return new ShopResource($shop);
    }
}
