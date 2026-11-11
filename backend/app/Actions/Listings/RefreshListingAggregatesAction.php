<?php

namespace App\Actions\Listings;

use App\Models\ShopProduct;

/**
 * Keeps the denormalized listing columns (from-price, compare-at, total
 * stock) in sync with the per-variant rows. Called after any change to a
 * listing's variants, stock movements included.
 */
final class RefreshListingAggregatesAction
{
    public function execute(ShopProduct $listing): void
    {
        $variants = $listing->variants()->get(['price', 'compare_at_price', 'stock', 'is_active']);
        $active = $variants->where('is_active', true);
        $pool = $active->isNotEmpty() ? $active : $variants;

        $cheapest = $pool->sortBy(fn ($v) => (float) $v->price)->first();

        $listing->forceFill([
            'price' => $cheapest?->price ?? 0,
            'compare_at_price' => $cheapest?->compare_at_price,
            'stock' => (int) $active->sum('stock'),
        ])->saveQuietly();
    }
}
