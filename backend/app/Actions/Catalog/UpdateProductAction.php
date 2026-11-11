<?php

namespace App\Actions\Catalog;

use App\Actions\Media\SyncImageAction;
use App\DTOs\Catalog\ProductData;
use App\Models\Product;
use App\Services\Catalog\SkuGenerator;
use Illuminate\Support\Facades\DB;

final class UpdateProductAction
{
    public function __construct(
        private readonly SyncImageAction $syncImage,
        private readonly SkuGenerator $skus,
    ) {}

    public function execute(Product $product, ProductData $data): Product
    {
        DB::transaction(function () use ($product, $data) {
            $product->update($data->toAttributes());

            if ($product->sku === null) {
                $product->forceFill(['sku' => $this->skus->forProduct($product)])->saveQuietly();
            }

            // A simple product's single default variant mirrors the product.
            $default = $product->variants()->where('option_signature', '')->first();
            if ($default !== null) {
                $default->update([
                    'sku' => $this->skus->unique($product->sku, $default->getKey()),
                    'base_price' => $product->base_price,
                ]);
            }
        });

        $this->syncImage->execute($product, $data->thumbnail, $data->removeThumbnail, 'thumbnail');

        return $product;
    }
}
