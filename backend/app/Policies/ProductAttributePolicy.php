<?php

namespace App\Policies;

use App\Enums\PermissionName as P;
use App\Models\ProductAttribute;
use App\Models\User;

class ProductAttributePolicy
{
    /** Shop owners need to read attributes to build product variants. */
    public function viewAny(User $user): bool
    {
        return $user->canAny([P::AttributesView->value, P::OwnProductsManage->value]);
    }

    public function create(User $user): bool
    {
        return $user->can(P::AttributesCreate->value);
    }

    public function update(User $user, ProductAttribute $attribute): bool
    {
        return $user->can(P::AttributesUpdate->value);
    }

    public function delete(User $user, ProductAttribute $attribute): bool
    {
        return $user->can(P::AttributesDelete->value);
    }
}
