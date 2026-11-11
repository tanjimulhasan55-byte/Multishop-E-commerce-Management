<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Actions\Listings\AddProductToShopAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Shop\StoreListingRequest;
use App\Http\Resources\Api\V1\ListingResource;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;

/**
 * @tags Shop Owner
 */
class StoreListingController extends Controller
{
    /**
     * Add a catalog product to your shop with your own price, stock,
     * optional title/description and thumbnail.
     */
    public function __invoke(StoreListingRequest $request, Shop $shop, AddProductToShopAction $action): JsonResponse
    {
        $listing = $action->execute($shop, Product::findOrFail($request->listedProductId()), $request->toDto());

        return (new ListingResource($listing->load(ShopProduct::displayRelations())))
            ->response()
            ->setStatusCode(201);
    }
}
