<?php

namespace App\Policies;

use App\Enums\PermissionName as P;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::CategoriesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(P::CategoriesCreate->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can(P::CategoriesUpdate->value);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can(P::CategoriesDelete->value);
    }
}
