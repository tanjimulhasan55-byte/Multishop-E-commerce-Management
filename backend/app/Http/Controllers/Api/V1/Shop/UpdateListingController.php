<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Actions\Listings\UpdateListingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Shop\UpdateListingRequest;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\ShopProduct;

/**
 * @tags Shop Owner
 */
class UpdateListingController extends Controller
{
    /**
     * Change your shop's price, stock, details or thumbnail for a product.
     * For file uploads send `POST` with `_method=PATCH` as multipart/form-data.
     */
    public function __invoke(UpdateListingRequest $request, ShopProduct $listing, UpdateListingAction $action): ListingResource
    {
        $listing = $action->execute($listing, $request->toDto());

        return new ListingResource($listing->load(ShopProduct::displayRelations()));
    }
}
