<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent: safe to run on every deploy. Permissions are synced from
 * the PermissionName enum; system roles get their defaults only when they
 * are first created, so later edits made in the Roles module are kept
 * (the super-admin role always receives every permission).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // Remove permissions that no longer exist in code.
        Permission::query()->where('guard_name', 'web')
            ->whereNotIn('name', PermissionName::values())
            ->delete();

        foreach (RoleName::cases() as $roleName) {
            $role = Role::findOrCreate($roleName->value, 'web');

            if ($roleName === RoleName::Admin || $role->wasRecentlyCreated) {
                $role->syncPermissions(array_map(fn ($p) => $p->value, $roleName->defaultPermissions()));
            }
        }

        // Example custom role showing the module: catalog staff with limited panel access.
        $manager = Role::findOrCreate('catalog_manager', 'web');
        if ($manager->wasRecentlyCreated) {
            $manager->syncPermissions([
                PermissionName::DashboardView->value,
                PermissionName::ProductsView->value,
                PermissionName::ProductsCreate->value,
                PermissionName::ProductsUpdate->value,
                PermissionName::CategoriesView->value,
                PermissionName::CategoriesCreate->value,
                PermissionName::CategoriesUpdate->value,
                PermissionName::AttributesView->value,
                PermissionName::AttributesCreate->value,
                PermissionName::AttributesUpdate->value,
                PermissionName::ShopsView->value,
                PermissionName::ListingsView->value,
                PermissionName::OrdersView->value,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
