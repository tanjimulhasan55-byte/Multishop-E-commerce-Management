<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The sellable unit of a catalog product, with its own SKU. A product
 * without options has exactly one "default" variant (empty signature).
 */
class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'sku', 'option_signature', 'base_price', 'position'];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return BelongsToMany<AttributeValue, $this> */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class);
    }

    /** @return HasMany<ShopProductVariant, $this> */
    public function shopVariants(): HasMany
    {
        return $this->hasMany(ShopProductVariant::class);
    }

    public function isDefault(): bool
    {
        return $this->option_signature === '';
    }

    /** @param list<int> $valueIds */
    public static function signatureFor(array $valueIds): string
    {
        $ids = array_map('intval', $valueIds);
        sort($ids);

        return implode('-', $ids);
    }

    /**
     * Options ordered by attribute, e.g. [['attribute' => 'Color', 'value' => 'Red', ...], ...].
     * Requires attributeValues.attribute to be loaded for efficiency.
     *
     * @return list<array<string, mixed>>
     */
    public function options(): array
    {
        return $this->attributeValues
            ->sortBy(fn (AttributeValue $v) => [$v->attribute?->sort_order, $v->attribute_id])
            ->map(fn (AttributeValue $v) => [
                'attribute_id' => $v->attribute_id,
                'attribute' => $v->attribute?->name,
                'type' => $v->attribute?->type,
                'value_id' => $v->getKey(),
                'value' => $v->value,
                'color_hex' => $v->color_hex,
            ])->values()->all();
    }

    /** "Red / XL" — or null for the default variant. */
    public function label(?string $locale = null): ?string
    {
        if ($this->isDefault()) {
            return null;
        }

        $locale ??= app()->getLocale();

        return $this->attributeValues
            ->sortBy(fn (AttributeValue $v) => [$v->attribute?->sort_order, $v->attribute_id])
            ->map(fn (AttributeValue $v) => $v->getTranslation('value', $locale))
            ->implode(' / ');
    }
}
