<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Actions\Catalog\Variants\DeleteVariantAction;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Response;

/**
 * @tags Catalog
 */
class DeleteVariantController extends Controller
{
    /** Remove a variant (a product always keeps at least one). */
    public function __invoke(ProductVariant $variant, DeleteVariantAction $action): Response
    {
        $this->authorize('update', $variant->product);

        $action->execute($variant);

        return response()->noContent();
    }
}
