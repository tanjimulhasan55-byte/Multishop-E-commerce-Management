<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * A variant option type such as "Size" or "Color".
 * (Named ProductAttribute to avoid clashing with Eloquent's Attribute class.)
 */
class ProductAttribute extends Model
{
    use HasFactory, HasSlug, HasTranslations;

    public const TYPE_SELECT = 'select';

    public const TYPE_COLOR = 'color';

    protected $table = 'attributes';

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['name', 'type', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (self $m) => $m->getTranslation('name', 'en', false))
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /** @return HasMany<AttributeValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->orderBy('sort_order')->orderBy('id');
    }
}
