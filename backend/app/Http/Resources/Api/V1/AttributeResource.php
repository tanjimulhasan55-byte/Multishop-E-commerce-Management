<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @mixin ProductAttribute */
class AttributeResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'attributes';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'sort_order' => $this->sort_order,
            'values' => $this->whenLoaded('values', fn () => $this->values->map(fn ($v) => [
                'id' => $v->getKey(),
                'value' => $v->value,
                'code' => $v->code,
                'color_hex' => $v->color_hex,
                'sort_order' => $v->sort_order,
            ])->all()),
        ];
    }
}
