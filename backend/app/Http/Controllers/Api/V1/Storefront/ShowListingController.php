<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\ShopProduct;

/**
 * @tags Storefront
 */
class ShowListingController extends Controller
{
    /**
     * One customer-visible product listing (404 if not assigned to an active shop).
     *
     * @unauthenticated
     */
    public function __invoke(int $listing): ListingResource
    {
        $model = ShopProduct::query()
            ->visibleToCustomers()
            ->with([...ShopProduct::displayRelations(), 'shop.media'])
            ->findOrFail($listing);

        return new ListingResource($model);
    }
}
