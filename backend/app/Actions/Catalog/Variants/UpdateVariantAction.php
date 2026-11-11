<?php

namespace App\Actions\Catalog\Variants;

use App\Models\ProductVariant;

final class UpdateVariantAction
{
    public function execute(ProductVariant $variant, string $sku, string $basePrice): ProductVariant
    {
        $variant->update(['sku' => $sku, 'base_price' => $basePrice]);

        return $variant;
    }
}
