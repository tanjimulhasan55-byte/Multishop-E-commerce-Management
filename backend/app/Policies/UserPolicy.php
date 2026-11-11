<?php

namespace App\Policies;

use App\Enums\PermissionName as P;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::UsersView->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(P::UsersView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(P::UsersCreate->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(P::UsersUpdate->value);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can(P::UsersDelete->value) && ! $user->is($model);
    }
}
