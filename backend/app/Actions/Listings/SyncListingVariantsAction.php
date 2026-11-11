<?php

namespace App\Actions\Listings;

use App\Models\Product;
use App\Models\ShopProductVariant;

/**
 * When a catalog product gains variants, every shop already listing it gets
 * matching shop-variant rows (at the shop's current price, zero stock) so
 * owners only need to fill in stock.
 */
final class SyncListingVariantsAction
{
    public function __construct(private readonly RefreshListingAggregatesAction $refresh) {}

    public function execute(Product $product): void
    {
        $variantIds = $product->variants()->pluck('id');

        foreach ($product->listings()->with('variants')->get() as $listing) {
            $existing = $listing->variants->pluck('product_variant_id');
            $defaultPrice = $listing->variants->min('price') ?? $listing->price;

            foreach ($variantIds->diff($existing) as $variantId) {
                ShopProductVariant::create([
                    'shop_product_id' => $listing->getKey(),
                    'product_variant_id' => $variantId,
                    'price' => $defaultPrice ?: $product->base_price,
                    'stock' => 0,
                    'is_active' => true,
                ]);
            }

            $this->refresh->execute($listing);
        }
    }
}
