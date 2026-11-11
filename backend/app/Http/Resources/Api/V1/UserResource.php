<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

/** @mixin User */
class UserResource extends JsonApiResource
{
    public function toType(Request $request): string
    {
        return 'users';
    }

    public function toAttributes(Request $request): array
    {
        // On login/register the user is held by the JWT guard, not yet by the request.
        $viewerId = $request->user()?->getKey() ?? auth('api')->id();
        $isSelf = $viewerId !== null && (int) $viewerId === (int) $this->getKey();

        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->all()),
            // Only the user themselves gets their effective permission list (drives SPA menus).
            'permissions' => $this->when($isSelf, fn () => $this->getAllPermissions()->pluck('name')->values()->all()),
            'shops_count' => $this->whenCounted('shops'),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
