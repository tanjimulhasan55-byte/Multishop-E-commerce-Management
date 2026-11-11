<?php

namespace App\Services\Catalog;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * SKU conventions:
 *   product / default variant:  TSHIRT-01           (or PRD-000042 when none given)
 *   option variant:             TSHIRT-01-RED-XL    (codes in attribute order)
 * Collisions get a numeric suffix (-2, -3, ...). SKUs are generated once and
 * never silently rewritten afterwards — they are printed on labels and
 * referenced by order history.
 */
final class SkuGenerator
{
    public function forProduct(Product $product): string
    {
        return $product->sku ?: 'PRD-'.str_pad((string) $product->getKey(), 6, '0', STR_PAD_LEFT);
    }

    /** @param Collection<int, AttributeValue> $values with `attribute` loaded */
    public function forVariant(Product $product, Collection $values): string
    {
        $codes = $values
            ->sortBy(fn (AttributeValue $v) => [$v->attribute->sort_order, $v->attribute_id])
            ->map(fn (AttributeValue $v) => self::code($v->code))
            ->implode('-');

        return $this->unique(Str::limit($this->forProduct($product).($codes !== '' ? '-'.$codes : ''), 58, ''));
    }

    public function unique(string $sku, ?int $ignoreVariantId = null): string
    {
        $candidate = $sku;
        $n = 2;

        while (ProductVariant::query()
            ->where('sku', $candidate)
            ->when($ignoreVariantId, fn ($q) => $q->whereKeyNot($ignoreVariantId))
            ->exists()) {
            $candidate = $sku.'-'.$n++;
        }

        return $candidate;
    }

    public static function code(string $value): string
    {
        return Str::of($value)->ascii()->upper()->replaceMatches('/[^A-Z0-9]+/', '')->limit(12, '')->value() ?: 'X';
    }
}
