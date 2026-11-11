<?php

namespace App\Http\Controllers\Api\V1\Admin\Products;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;

/**
 * @tags Admin
 */
class ShowProductController extends Controller
{
    public function __invoke(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        return new ProductResource($product->load([...Product::detailRelations(), 'creator'])->loadCount('listings'));
    }
}
