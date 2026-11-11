<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\DeleteProductAction;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;

/**
 * @tags Catalog
 */
class DeleteProductController extends Controller
{
    /** Delete a product you created (not allowed while other shops list it). */
    public function __invoke(Product $product, DeleteProductAction $action): Response
    {
        $this->authorize('delete', $product);

        $action->execute($product);

        return response()->noContent();
    }
}
