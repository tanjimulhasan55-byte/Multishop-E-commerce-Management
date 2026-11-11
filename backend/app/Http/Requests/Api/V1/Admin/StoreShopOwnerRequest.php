<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\Users\UserData;
use App\Enums\RoleName;
use Illuminate\Support\Arr;

/**
 * Shop-owner accounts endpoint: same rules as a user, but the role is
 * fixed to shop_owner and cannot be chosen by the client.
 */
class StoreShopOwnerRequest extends StoreUserRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['roles', 'roles.*']);
    }

    public function toDto(): UserData
    {
        return UserData::fromArray($this->validated(), forceRoles: [RoleName::ShopOwner->value]);
    }
}
