<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\DeleteRoleAction;
use App\Actions\Users\SaveRoleAction;
use App\DataTables\RolesDataTable;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreRoleRequest;
use App\Http\Requests\Api\V1\Admin\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Roles & Permissions module: admins define custom roles and choose exactly
 * which permissions each one grants.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index');
    }

    public function data(Request $request, RolesDataTable $table): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return $table->toJson($request);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.form', ['role' => null, 'groups' => PermissionName::grouped(), 'granted' => []]);
    }

    public function store(StoreRoleRequest $request, SaveRoleAction $action): RedirectResponse
    {
        $role = $action->execute($request->toDto());

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('admin.roles.form', [
            'role' => $role,
            'groups' => PermissionName::grouped(),
            'granted' => $role->permissions()->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, SaveRoleAction $action): RedirectResponse
    {
        $action->execute($request->toDto(), $role);

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Role $role, DeleteRoleAction $action): RedirectResponse
    {
        $this->authorize('delete', $role);
        $action->execute($role);

        return back()->with('success', 'Role deleted.');
    }
}
