<?php

namespace App\Actions\Users;

use App\DTOs\Users\RoleData;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class SaveRoleAction
{
    public function execute(RoleData $data, ?Role $role = null): Role
    {
        if ($role !== null && RoleName::isSystem($role->name) && $data->name !== $role->name) {
            throw ValidationException::withMessages(['name' => __('System roles cannot be renamed.')]);
        }

        // The super-admin role always holds every permission.
        $permissions = $role?->name === RoleName::Admin->value
            ? PermissionName::values()
            : $data->permissions;

        return DB::transaction(function () use ($data, $role, $permissions) {
            $role ??= new Role(['guard_name' => 'web']);
            $role->name = $data->name;
            $role->save();

            $role->syncPermissions($permissions);

            return $role;
        });
    }
}
