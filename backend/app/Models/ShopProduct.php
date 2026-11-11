<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ShopStatus;
use App\Models\Concerns\HasThumbnail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;

/**
 * A shop's listing of a catalog product (shop-specific price, details and
 * thumbnail). This is what customers browse and buy.
 */
class ShopProduct extends Pivot implements HasMedia
{
    use HasFactory, HasThumbnail, HasTranslations;

    protected $table = 'shop_products';

    public $incrementing = true;

    /** @var list<string> */
    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'shop_id',
        'product_id',
        'price',
        'compare_at_price',
        'stock',
        'title',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Shop, $this> */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<ShopProductVariant, $this> */
    public function variants(): HasMany
    {
        // Explicit key: Pivot models don't infer their own foreign key name.
        return $this->hasMany(ShopProductVariant::class, 'shop_product_id');
    }

    /** Eager-load set needed to render a listing with its variants. */
    public static function displayRelations(): array
    {
        return [
            'media',
            'product.media',
            'product.category',
            'variants.variant.attributeValues.attribute',
        ];
    }

    /**
     * The single source of truth for "what customers may see":
     * listing active + shop active (not deleted) + product active (not deleted).
     */
    #[Scope]
    protected function visibleToCustomers(Builder $query): void
    {
        $query->where('shop_products.is_active', true)
            ->whereHas('shop', fn (Builder $q) => $q->where('status', ShopStatus::Active->value))
            ->whereHas('product', fn (Builder $q) => $q->where('status', ProductStatus::Active->value));
    }

    public function isVisibleToCustomers(): bool
    {
        return $this->is_active
            && $this->shop?->isActive()
            && $this->product?->isActive();
    }

    /** Shop-specific title, falling back to the catalog product name. */
    public function displayName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->getTranslation('title', $locale, false)
            ?: $this->getTranslation('title', 'en', false)
            ?: (string) $this->product?->getTranslation('name', $locale);
    }

    public function displayDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $this->getTranslation('description', $locale, false)
            ?: $this->getTranslation('description', 'en', false)
            ?: $this->product?->getTranslation('description', $locale);
    }

    /** Shop thumbnail if uploaded, otherwise the catalog product's image. */
    public function displayImages(): ?array
    {
        return $this->thumbnailUrls() ?? $this->product?->thumbnailUrls();
    }
}
