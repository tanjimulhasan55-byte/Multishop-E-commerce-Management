<?php

namespace App\Models;

use App\Enums\ShopStatus;
use App\Models\Concerns\HasThumbnail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Shop extends Model implements HasMedia
{
    use HasFactory, HasSlug, HasThumbnail, HasTranslations, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'email',
        'phone',
        'address',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShopStatus::class,
        ];
    }

    protected function thumbnailCollection(): string
    {
        return 'logo';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (self $m) => $m->getTranslation('name', 'en', false))
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<ShopProduct, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'shop_products')
            ->using(ShopProduct::class)
            ->withPivot(['id', 'price', 'compare_at_price', 'stock', 'is_active'])
            ->withTimestamps();
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isActive(): bool
    {
        return $this->status === ShopStatus::Active;
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ShopStatus::Active->value);
    }

    #[Scope]
    protected function ownedBy(Builder $query, User|int $user): void
    {
        $query->where('owner_id', $user instanceof User ? $user->getKey() : $user);
    }
}
