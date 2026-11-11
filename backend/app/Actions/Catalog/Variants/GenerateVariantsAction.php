<?php

namespace App\Actions\Catalog\Variants;

use App\Actions\Listings\SyncListingVariantsAction;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\SkuGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Builds every combination of the chosen option values (Size × Color …),
 * each as a variant with its own generated SKU. Existing combinations are
 * kept untouched, so the action is safe to run again after adding values.
 */
final class GenerateVariantsAction
{
    public const MAX_VARIANTS = 100;

    public function __construct(
        private readonly SkuGenerator $skus,
        private readonly SyncListingVariantsAction $syncListings,
    ) {}

    /**
     * @param  list<int>  $valueIds
     * @return Collection<int, ProductVariant> newly created variants
     */
    public function execute(Product $product, array $valueIds, ?string $basePrice = null): Collection
    {
        $groups = AttributeValue::query()
            ->with('attribute')
            ->whereKey($valueIds)
            ->get()
            ->groupBy('attribute_id')
            ->sortBy(fn (Collection $g) => [$g->first()->attribute->sort_order, $g->first()->attribute_id])
            ->values();

        if ($groups->isEmpty()) {
            throw ValidationException::withMessages(['attribute_value_ids' => __('Choose at least one option value.')]);
        }

        $combinations = $this->cartesian($groups->map->values()->all());

        if (count($combinations) > self::MAX_VARIANTS) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => __('That would create :n variants; the maximum is :max.', ['n' => count($combinations), 'max' => self::MAX_VARIANTS]),
            ]);
        }

        $created = DB::transaction(function () use ($product, $combinations, $basePrice) {
            $existing = $product->variants()->pluck('option_signature')->flip();
            $position = (int) $product->variants()->max('position');
            $created = collect();

            foreach ($combinations as $combo) {
                $combo = collect($combo);
                $signature = ProductVariant::signatureFor($combo->pluck('id')->all());

                if ($existing->has($signature)) {
                    continue;
                }

                $variant = $product->variants()->create([
                    'sku' => $this->skus->forVariant($product, $combo),
                    'option_signature' => $signature,
                    'base_price' => $basePrice ?? $product->base_price,
                    'position' => ++$position,
                ]);
                $variant->attributeValues()->attach($combo->pluck('id')->all());
                $created->push($variant);
            }

            // A product with real options no longer needs its option-less default variant.
            if ($created->isNotEmpty()) {
                $product->variants()->where('option_signature', '')->delete();
            }

            return $created;
        });

        $this->syncListings->execute($product);

        return $created;
    }

    /**
     * @param  list<list<AttributeValue>>  $sets
     * @return list<list<AttributeValue>>
     */
    private function cartesian(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $set) {
            $next = [];
            foreach ($result as $prefix) {
                foreach ($set as $item) {
                    $next[] = [...$prefix, $item];
                }
            }
            $result = $next;
        }

        return $result;
    }
}
