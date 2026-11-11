<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Actions\Listings\RemoveListingAction;
use App\Http\Controllers\Controller;
use App\Models\ShopProduct;
use Illuminate\Http\Response;

/**
 * @tags Shop Owner
 */
class DeleteListingController extends Controller
{
    /** Remove a product from your shop (the catalog product is kept). */
    public function __invoke(ShopProduct $listing, RemoveListingAction $action): Response
    {
        $this->authorize('delete', $listing);

        $action->execute($listing);

        return response()->noContent();
    }
}
