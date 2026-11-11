<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * Nested categories: parent_id + materialized path ("/1/4/9/").
 */
class Category extends Model
{
    use HasFactory, HasSlug, HasTranslations;

    public const MAX_DEPTH = 5;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['parent_id', 'name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'depth' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The path needs the row id, so it is written right after insert.
        static::created(function (self $category) {
            $category->refreshPath();
        });

        static::updated(function (self $category) {
            if ($category->wasChanged('parent_id')) {
                $category->refreshPath();
            }
        });
    }

    /**
     * Recompute this node's path/depth and re-prefix every descendant
     * (found by the old path prefix — an indexed range scan).
     */
    public function refreshPath(): void
    {
        $parent = $this->parent_id ? self::query()->find($this->parent_id) : null;

        $oldPath = (string) $this->path;
        $newPath = ($parent?->path ?? '/').$this->getKey().'/';

        $this->forceFill(['path' => $newPath, 'depth' => self::depthOf($newPath)])->saveQuietly();

        // A freshly inserted row has no path yet and no descendants.
        if ($oldPath === '' || $oldPath === '/' || $oldPath === $newPath) {
            return;
        }

        DB::transaction(function () use ($oldPath, $newPath) {
            self::query()
                ->where('path', 'like', $oldPath.'%')
                ->whereKeyNot($this->getKey())
                ->lazyById()
                ->each(function (self $descendant) use ($oldPath, $newPath) {
                    $path = $newPath.substr($descendant->path, strlen($oldPath));
                    $descendant->forceFill(['path' => $path, 'depth' => self::depthOf($path)])->saveQuietly();
                });
        });
    }

    private static function depthOf(string $path): int
    {
        return max(0, count(array_filter(explode('/', $path))) - 1);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (self $m) => $m->getTranslation('name', 'en', false))
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return list<int> ancestor ids, root first (excluding self) */
    public function ancestorIds(): array
    {
        $ids = array_map('intval', array_filter(explode('/', (string) $this->path)));
        array_pop($ids);

        return $ids;
    }

    public function isAncestorOf(self|int $other): bool
    {
        $other = $other instanceof self ? $other : self::query()->find($other);

        return $other !== null && str_starts_with((string) $other->path, (string) $this->path) && ! $other->is($this);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** This category and every descendant (one indexed prefix scan). */
    #[Scope]
    protected function subtreeOf(Builder $query, self $category): void
    {
        $query->where('path', 'like', $category->path.'%');
    }

    /** Human readable "Fashion › Men › Shirts". */
    public function breadcrumb(string $separator = ' › '): string
    {
        $ancestors = self::query()->whereKey($this->ancestorIds())->get()->sortBy('depth');

        return $ancestors->push($this)->map(fn (self $c) => $c->name)->implode($separator);
    }
}
