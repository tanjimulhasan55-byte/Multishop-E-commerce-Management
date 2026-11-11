<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersCells;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RolesDataTable
{
    use RendersCells;

    public function toJson(Request $request): JsonResponse
    {
        $actor = $request->user();
        $total = count(PermissionName::cases());

        $query = Role::query()->select('roles.*')->withCount(['permissions', 'users']);

        return DataTables::eloquent($query)
            ->editColumn('name', function (Role $r) {
                $system = RoleName::tryFrom($r->name);

                return '<p class="font-semibold text-slate-900">'.e($system?->label() ?? str($r->name)->headline()).'</p>'
                    .'<p class="text-xs text-slate-500"><code>'.e($r->name).'</code></p>';
            })
            ->addColumn('type', fn (Role $r) => RoleName::isSystem($r->name) ? $this->badge('System', 'indigo') : $this->badge('Custom', 'amber'))
            ->editColumn('permissions_count', function (Role $r) use ($total) {
                $count = $r->name === RoleName::Admin->value ? $total : $r->permissions_count;
                $pct = $total ? round($count / $total * 100) : 0;

                return $this->blade(<<<'BLADE'
                    <div class="w-40">
                        <div class="flex justify-between text-xs"><span class="font-semibold text-slate-700">{{ $count }} / {{ $total }}</span><span class="text-slate-400">{{ $pct }}%</span></div>
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-violet-500" style="width: {{ $pct }}%"></div></div>
                    </div>
                BLADE, compact('count', 'total', 'pct'));
            })
            ->editColumn('users_count', fn (Role $r) => '<span class="tabular-nums font-semibold">'.$r->users_count.'</span>')
            ->addColumn('actions', fn (Role $r) => $this->actions(
                edit: $actor->can('update', $r) ? route('admin.roles.edit', $r) : null,
                delete: $actor->can('delete', $r) ? route('admin.roles.destroy', $r) : null,
                confirm: "Delete role \"{$r->name}\"?",
            ))
            ->rawColumns(['name', 'type', 'permissions_count', 'users_count', 'actions'])
            ->toJson();
    }
}
