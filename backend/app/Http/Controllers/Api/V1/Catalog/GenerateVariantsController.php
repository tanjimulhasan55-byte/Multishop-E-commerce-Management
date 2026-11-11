<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\Variants\GenerateVariantsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\GenerateVariantsRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;

/**
 * @tags Catalog
 */
class GenerateVariantsController extends Controller
{
    /**
     * Create one variant (with its own auto-generated SKU) for every
     * combination of the chosen option values. Existing combinations are
     * kept; shops already listing the product get the new variants at zero stock.
     */
    public function __invoke(GenerateVariantsRequest $request, Product $product, GenerateVariantsAction $action): ProductResource
    {
        $action->execute($product, $request->valueIds(), $request->basePrice());

        return new ProductResource($product->refresh()->load(Product::detailRelations()));
    }
}
