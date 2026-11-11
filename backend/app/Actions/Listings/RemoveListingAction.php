<?php

namespace App\Actions\Listings;

use App\Models\ShopProduct;
use Illuminate\Support\Facades\DB;

final class RemoveListingAction
{
    /**
     * Removes the product from the shop only — the catalog product stays.
     * Past order items keep their snapshot (shop_product_id is nulled by FK).
     */
    public function execute(ShopProduct $listing): void
    {
        DB::transaction(function () use ($listing) {
            $listing->clearMediaCollection('thumbnail');
            $listing->delete();
        });
    }
}
