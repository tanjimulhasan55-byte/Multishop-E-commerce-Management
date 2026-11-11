<?php

namespace App\Actions\Listings;

use App\Actions\Media\SyncImageAction;
use App\DTOs\Listings\ListingData;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddProductToShopAction
{
    public function __construct(
        private readonly SyncImageAction $syncImage,
        private readonly RefreshListingAggregatesAction $refresh,
    ) {}

    public function execute(Shop $shop, Product $product, ListingData $data): ShopProduct
    {
        if (! $product->isActive()) {
            throw ValidationException::withMessages([
                'product_id' => __('Only active catalog products can be listed.'),
            ]);
        }

        try {
            $listing = DB::transaction(function () use ($shop, $product, $data) {
                $listing = ShopProduct::create([
                    ...$data->toAttributes(),
                    'shop_id' => $shop->getKey(),
                    'product_id' => $product->getKey(),
                ]);

                // One shop-variant per catalog variant: overrides first, then shop-wide defaults.
                foreach ($product->variants()->get() as $variant) {
                    $override = $data->variants[$variant->getKey()] ?? null;

                    $listing->variants()->create([
                        'product_variant_id' => $variant->getKey(),
                        ...($override?->toAttributes() ?? [
                            'price' => $data->price ?? $variant->base_price,
                            'compare_at_price' => $data->compareAtPrice,
                            'stock' => $data->stock ?? 0,
                            'is_active' => true,
                        ]),
                    ]);
                }

                $this->refresh->execute($listing);

                return $listing;
            });
        } catch (UniqueConstraintViolationException) {
            // Race-safe: the (shop_id, product_id) unique index is the final guard.
            throw ValidationException::withMessages([
                'product_id' => __('This product is already listed in the shop.'),
            ]);
        }

        $this->syncImage->execute($listing, $data->thumbnail, false, 'thumbnail');

        return $listing;
    }
}
