<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\CreateProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * @tags Catalog
 */
class StoreProductController extends Controller
{
    /**
     * Create a product. It joins the shared catalog, so every shop owner can
     * list it. Send as multipart/form-data to upload `thumbnail`.
     */
    public function __invoke(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute($request->toDto(), $request->user());

        return (new ProductResource($product->load(Product::detailRelations())))->response()->setStatusCode(201);
    }
}
