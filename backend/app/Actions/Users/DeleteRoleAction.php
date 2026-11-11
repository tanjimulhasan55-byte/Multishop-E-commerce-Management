<?php

namespace App\Actions\Users;

use App\Enums\RoleName;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class DeleteRoleAction
{
    public function execute(Role $role): void
    {
        if (RoleName::isSystem($role->name)) {
            throw ValidationException::withMessages(['role' => __('System roles cannot be deleted.')]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => __('Unassign this role from all users before deleting it.')]);
        }

        $role->delete();
    }
}
