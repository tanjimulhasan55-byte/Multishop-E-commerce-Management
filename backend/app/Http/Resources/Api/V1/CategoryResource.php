<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @mixin Category */
class CategoryResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'categories';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'depth' => $this->depth,
            'path' => $this->path,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'translations' => ['name' => (object) $this->getTranslations('name')],
            'products_count' => $this->whenCounted('products'),
        ];
    }
}
