<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\DeleteUserAction;
use App\Actions\Users\SaveUserAction;
use App\DataTables\UsersDataTable;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * All accounts (admins, staff with custom roles, shop owners, customers).
 * "Shop Owners" in the menu is this screen filtered by role.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'roles' => $this->roleOptions(),
            'statuses' => UserStatus::options(),
            'activeRole' => $request->string('role')->value() ?: null,
        ]);
    }

    public function data(Request $request, UsersDataTable $table): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $table->toJson($request);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', [
            'user' => null,
            'roles' => Role::query()->withCount('permissions')->orderBy('name')->get(),
            'statuses' => UserStatus::options(),
            'presetRoles' => array_filter([$request->string('role')->value()]),
        ]);
    }

    public function store(StoreUserRequest $request, SaveUserAction $action): RedirectResponse
    {
        $user = $action->execute($request->toDto(), actor: $request->user());

        return redirect()->route('admin.users.index')->with('success', "Account for {$user->name} created.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.form', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->withCount('permissions')->orderBy('name')->get(),
            'statuses' => UserStatus::options(),
            'presetRoles' => [],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, SaveUserAction $action): RedirectResponse
    {
        $action->execute($request->toDto(), $user, $request->user());

        return redirect()->route('admin.users.index')->with('success', "{$user->name} updated.");
    }

    public function destroy(Request $request, User $user, DeleteUserAction $action): RedirectResponse
    {
        $this->authorize('delete', $user);
        $action->execute($user, $request->user());

        return back()->with('success', 'Account deleted.');
    }

    /** @return array<string, string> */
    private function roleOptions(): array
    {
        return Role::query()->orderBy('name')->pluck('name', 'name')
            ->map(fn ($n) => RoleName::tryFrom($n)?->label() ?? str($n)->headline()->value())
            ->all();
    }
}
