<?php

namespace App\Actions\Catalog;

use App\Actions\Media\SyncImageAction;
use App\DTOs\Catalog\ProductData;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\SkuGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Adds a product to the shared catalog. Products created by admins and by
 * shop owners are equally available for every shop to list.
 *
 * Every product starts with one default variant (its sellable SKU); option
 * variants (Size × Color …) can then be generated from attribute values.
 */
final class CreateProductAction
{
    public function __construct(
        private readonly SyncImageAction $syncImage,
        private readonly SkuGenerator $skus,
    ) {}

    public function execute(ProductData $data, User $creator): Product
    {
        $product = DB::transaction(function () use ($data, $creator) {
            $product = Product::create([
                ...$data->toAttributes(),
                'created_by' => $creator->getKey(),
            ]);

            if ($product->sku === null) {
                $product->forceFill(['sku' => $this->skus->forProduct($product)])->saveQuietly();
            }

            $product->variants()->create([
                'sku' => $this->skus->unique($product->sku),
                'option_signature' => '',
                'base_price' => $product->base_price,
                'position' => 0,
            ]);

            return $product;
        });

        $this->syncImage->execute($product, $data->thumbnail, false, 'thumbnail');

        return $product;
    }
}
