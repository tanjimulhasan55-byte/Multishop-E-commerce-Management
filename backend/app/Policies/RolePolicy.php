<?php

namespace App\Policies;

use App\Enums\PermissionName as P;
use App\Enums\RoleName;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::RolesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(P::RolesCreate->value);
    }

    public function update(User $user, Role $role): bool
    {
        // The super-admin role's permissions are fixed (always everything).
        return $user->can(P::RolesUpdate->value) && $role->name !== RoleName::Admin->value;
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(P::RolesDelete->value) && ! RoleName::isSystem($role->name);
    }
}
