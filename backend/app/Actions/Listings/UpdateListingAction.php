<?php

namespace App\Actions\Listings;

use App\Actions\Media\SyncImageAction;
use App\DTOs\Listings\ListingData;
use App\Models\ShopProduct;
use Illuminate\Support\Facades\DB;

final class UpdateListingAction
{
    public function __construct(
        private readonly SyncImageAction $syncImage,
        private readonly RefreshListingAggregatesAction $refresh,
    ) {}

    public function execute(ShopProduct $listing, ListingData $data): ShopProduct
    {
        DB::transaction(function () use ($listing, $data) {
            $listing->update($data->toAttributes());

            // Shop-wide quick edits ("set every size to 1,200 ৳").
            $bulk = array_filter([
                'price' => $data->price,
                'compare_at_price' => $data->compareAtPrice,
                'stock' => $data->stock,
            ], fn ($v) => $v !== null);

            if ($bulk !== []) {
                $listing->variants()->update($bulk);
            }

            foreach ($data->variants as $variantId => $variant) {
                $listing->variants()
                    ->where('product_variant_id', $variantId)
                    ->update($variant->toAttributes());
            }

            $this->refresh->execute($listing);
        });

        $this->syncImage->execute($listing, $data->thumbnail, $data->removeThumbnail, 'thumbnail');

        return $listing->refresh();
    }
}
