<?php

namespace App\Actions\Catalog;

use App\Models\Product;

final class DeleteProductAction
{
    /**
     * Soft delete: the product disappears from the catalog and storefronts
     * immediately (visibility scopes exclude trashed products), while order
     * history that references it stays intact.
     */
    public function execute(Product $product): void
    {
        $product->delete();
    }
}
