<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\UpdateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;

/**
 * @tags Catalog
 */
class UpdateProductController extends Controller
{
    /**
     * Update a product you created. For file uploads send `POST` with
     * `_method=PATCH` as multipart/form-data.
     */
    public function __invoke(UpdateProductRequest $request, Product $product, UpdateProductAction $action): ProductResource
    {
        return new ProductResource($action->execute($product, $request->toDto())->load(Product::detailRelations()));
    }
}
