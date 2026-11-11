<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersCells;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CategoriesDataTable
{
    use RendersCells;

    public function toJson(Request $request): JsonResponse
    {
        $user = $request->user();
        $names = Category::query()->get(['id', 'name'])->mapWithKeys(fn ($c) => [$c->id => $c->name]);

        $query = Category::query()->select('categories.*')->withCount(['products', 'children']);

        return DataTables::eloquent($query)
            ->addColumn('category', function (Category $c) use ($names) {
                $trail = collect($c->ancestorIds())->map(fn ($id) => $names[$id] ?? '')->filter()->implode(' › ');

                return $this->blade(<<<'BLADE'
                    <div class="flex items-center gap-2" style="padding-left: {{ $depth * 1.5 }}rem">
                        @if ($depth > 0)<span class="text-slate-300">└</span>@endif
                        <div>
                            <p class="font-semibold text-slate-900">{{ $name }}</p>
                            <p class="text-xs text-slate-500">{{ $trail ?: 'Top level' }}</p>
                        </div>
                    </div>
                BLADE, ['depth' => $c->depth, 'name' => $c->name, 'trail' => $trail]);
            })
            ->editColumn('slug', fn (Category $c) => '<code class="text-xs text-slate-500">'.e($c->slug).'</code>')
            ->editColumn('is_active', fn (Category $c) => $c->is_active ? $this->badge('Active', 'emerald') : $this->badge('Hidden', 'slate'))
            ->editColumn('products_count', fn (Category $c) => '<span class="tabular-nums">'.$c->products_count.'</span>')
            ->editColumn('children_count', fn (Category $c) => '<span class="tabular-nums">'.$c->children_count.'</span>')
            ->addColumn('actions', fn (Category $c) => $this->actions(
                edit: $user->can('update', $c) ? route('admin.categories.edit', $c) : null,
                delete: $user->can('delete', $c) ? route('admin.categories.destroy', $c) : null,
                confirm: "Delete category \"{$c->name}\"?",
            ))
            ->filterColumn('category', fn ($q, $k) => $q->where('categories.name_en', 'like', $this->likeTerm($k)))
            // Ordering by materialized path renders the tree in hierarchical order.
            ->orderColumn('category', 'categories.path $1')
            ->rawColumns(['category', 'slug', 'is_active', 'products_count', 'children_count', 'actions'])
            ->toJson();
    }
}
