<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\Users\UserData;
use App\Enums\RoleName;

class UpdateShopOwnerRequest extends StoreShopOwnerRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /** Keep any extra roles the owner already has; ensure shop_owner stays. */
    public function toDto(): UserData
    {
        $roles = $this->route('user')->getRoleNames()->push(RoleName::ShopOwner->value)->unique()->values()->all();

        return UserData::fromArray($this->validated(), forceRoles: $roles);
    }
}
