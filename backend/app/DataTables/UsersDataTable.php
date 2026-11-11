<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersCells;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UsersDataTable
{
    use RendersCells;

    public function toJson(Request $request): JsonResponse
    {
        $actor = $request->user();

        $query = User::query()
            ->select('users.*')
            ->with('roles')
            ->withCount('shops')
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return DataTables::eloquent($query)
            ->addColumn('user', fn (User $u) => $this->blade(<<<'BLADE'
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-100 to-violet-100 text-sm font-bold text-brand-700">
                        {{ Str::upper(Str::substr($u->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $u->name }} @if ($self)<span class="text-xs font-medium text-brand-600">(you)</span>@endif</p>
                        <p class="truncate text-xs text-slate-500">{{ $u->email }}@if ($u->phone) · {{ $u->phone }}@endif</p>
                    </div>
                </div>
            BLADE, ['u' => $u, 'self' => $actor->is($u)]))
            ->addColumn('roles', fn (User $u) => $u->roles->map(fn (Role $r) => $this->badge(str($r->name)->headline(), match ($r->name) {
                'admin' => 'rose', 'shop_owner' => 'indigo', 'customer' => 'slate', default => 'amber',
            }))->implode(' '))
            ->editColumn('status', fn (User $u) => $this->badge($u->status->label(), $u->status->color()))
            ->editColumn('shops_count', fn (User $u) => '<span class="tabular-nums">'.$u->shops_count.'</span>')
            ->editColumn('last_login_at', fn (User $u) => $u->last_login_at ? '<span class="text-slate-500">'.e($u->last_login_at->diffForHumans()).'</span>' : '<span class="text-slate-400">Never</span>')
            ->addColumn('actions', fn (User $u) => $this->actions(
                edit: $actor->can('update', $u) ? route('admin.users.edit', $u) : null,
                delete: $actor->can('delete', $u) ? route('admin.users.destroy', $u) : null,
                confirm: "Delete {$u->name}'s account?",
            ))
            ->filterColumn('user', fn ($q, $k) => $q->where(fn ($w) => $w->where('users.name', 'like', $this->likeTerm($k))
                ->orWhere('users.email', 'like', $this->likeTerm($k))
                ->orWhere('users.phone', 'like', $this->likeTerm($k))))
            ->orderColumn('user', 'users.name $1')
            ->rawColumns(['user', 'roles', 'status', 'shops_count', 'last_login_at', 'actions'])
            ->toJson();
    }
}
