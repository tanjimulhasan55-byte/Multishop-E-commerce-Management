<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/** One option of an attribute, e.g. Size = "XL" or Color = "Red". */
class AttributeValue extends Model
{
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['value'];

    protected $fillable = ['attribute_id', 'value', 'slug', 'code', 'color_hex', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /** @return BelongsTo<ProductAttribute, $this> */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    /** @return BelongsToMany<ProductVariant, $this> */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class);
    }
}
