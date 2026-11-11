<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\Variants\DeleteVariantAction;
use App\Actions\Catalog\Variants\GenerateVariantsAction;
use App\Actions\Catalog\Variants\UpdateVariantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\GenerateVariantsRequest;
use App\Http\Requests\Api\V1\Catalog\UpdateVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;

class ProductVariantController extends Controller
{
    public function generate(GenerateVariantsRequest $request, Product $product, GenerateVariantsAction $action): RedirectResponse
    {
        $created = $action->execute($product, $request->valueIds(), $request->basePrice());

        $message = $created->isEmpty()
            ? 'All of those combinations already exist.'
            : $created->count().' variant(s) created with their own SKUs.';

        return redirect()->to(route('admin.products.edit', $product).'#variants')->with('success', $message);
    }

    public function update(UpdateVariantRequest $request, ProductVariant $variant, UpdateVariantAction $action): RedirectResponse
    {
        $action->execute($variant, $request->sku(), $request->basePrice());

        return redirect()->to(route('admin.products.edit', $variant->product_id).'#variants')->with('success', "Variant {$variant->sku} updated.");
    }

    public function destroy(ProductVariant $variant, DeleteVariantAction $action): RedirectResponse
    {
        $this->authorize('update', $variant->product);
        $productId = $variant->product_id;
        $action->execute($variant);

        return redirect()->to(route('admin.products.edit', $productId).'#variants')->with('success', 'Variant deleted.');
    }
}
