<?php

namespace App\Actions\Catalog\Variants;

use App\Actions\Listings\RefreshListingAggregatesAction;
use App\Models\ProductVariant;
use App\Models\ShopProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteVariantAction
{
    public function __construct(private readonly RefreshListingAggregatesAction $refresh) {}

    public function execute(ProductVariant $variant): void
    {
        if (ProductVariant::query()->where('product_id', $variant->product_id)->count() <= 1) {
            throw ValidationException::withMessages(['variant' => __('A product must keep at least one variant.')]);
        }

        $listingIds = $variant->shopVariants()->pluck('shop_product_id');

        DB::transaction(fn () => $variant->delete()); // shop variants cascade

        ShopProduct::query()->whereKey($listingIds)->get()->each(fn ($l) => $this->refresh->execute($l));
    }
}
