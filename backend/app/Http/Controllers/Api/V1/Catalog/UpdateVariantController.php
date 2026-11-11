<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\Variants\UpdateVariantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\UpdateVariantRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * @tags Catalog
 */
class UpdateVariantController extends Controller
{
    /** Change a variant's SKU or suggested base price. */
    public function __invoke(UpdateVariantRequest $request, ProductVariant $variant, UpdateVariantAction $action): ProductResource
    {
        $action->execute($variant, $request->sku(), $request->basePrice());

        return new ProductResource($variant->product->load(Product::detailRelations()));
    }
}
