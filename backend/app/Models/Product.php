<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\HasThumbnail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * A product in the shared catalog. It is visible to customers only through
 * an active ShopProduct listing in an active shop.
 */
class Product extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasThumbnail, HasTranslations, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'category_id',
        'created_by',
        'name',
        'description',
        'sku',
        'base_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'status' => ProductStatus::class,
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (self $m) => $m->getTranslation('name', 'en', false))
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Eager-load set needed to render a product with its variants. */
    public static function detailRelations(): array
    {
        return ['media', 'category', 'variants.attributeValues.attribute'];
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    public function hasOptions(): bool
    {
        return $this->variants->contains(fn (ProductVariant $v) => ! $v->isDefault());
    }

    /**
     * Attributes used by this product's variants with their values, e.g.
     * [{attribute: Size, values: [S, M, XL]}] — drives variant pickers.
     * Requires variants.attributeValues.attribute to be loaded.
     *
     * @return list<array<string, mixed>>
     */
    public function optionMatrix(): array
    {
        return $this->variants
            ->flatMap(fn (ProductVariant $v) => $v->attributeValues)
            ->unique('id')
            ->groupBy('attribute_id')
            ->map(function ($values) {
                $attribute = $values->first()->attribute;

                return [
                    'attribute_id' => $attribute->getKey(),
                    'name' => $attribute->name,
                    'type' => $attribute->type,
                    'sort_order' => $attribute->sort_order,
                    'values' => $values->sortBy('sort_order')->map(fn (AttributeValue $v) => [
                        'id' => $v->getKey(),
                        'value' => $v->value,
                        'code' => $v->code,
                        'color_hex' => $v->color_hex,
                    ])->values()->all(),
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /** @return HasMany<ShopProduct, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function isActive(): bool
    {
        return $this->status === ProductStatus::Active;
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ProductStatus::Active->value);
    }

    /**
     * Search by English name / SKU. Uses the FULLTEXT index on MySQL and a
     * prefix-friendly LIKE elsewhere.
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $driver = DB::connection($query->getModel()->getConnectionName())->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true) && mb_strlen($term) >= 3) {
            // Boolean mode with prefix wildcard; strip operators users could inject.
            $safe = preg_replace('/[+\-><()~*"@]+/', ' ', $term);
            $words = collect(preg_split('/\s+/', (string) $safe, -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($w) => '+'.$w.'*')->implode(' ');

            $query->whereFullText(['name_en', 'sku'], $words, ['mode' => 'boolean']);

            return;
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $query->where(fn (Builder $q) => $q->where('name_en', 'like', $like)->orWhere('sku', 'like', $like));
    }
}
