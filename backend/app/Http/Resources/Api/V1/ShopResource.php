<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @mixin Shop */
class ShopResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'shops';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status->value,
            'logo' => $this->thumbnailUrls(),
            'owner_id' => $this->owner_id,
            'listings_count' => $this->whenCounted('listings'),
            'translations' => [
                'name' => (object) $this->getTranslations('name'),
                'description' => (object) $this->getTranslations('description'),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'owner' => UserResource::class,
        ];
    }
}
