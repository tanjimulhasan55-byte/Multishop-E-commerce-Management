<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersCells;
use App\Models\ProductAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AttributesDataTable
{
    use RendersCells;

    public function toJson(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ProductAttribute::query()->select('attributes.*')->with('values');

        return DataTables::eloquent($query)
            ->editColumn('name', fn (ProductAttribute $a) => '<p class="font-semibold text-slate-900">'.e($a->name).'</p><p class="text-xs text-slate-500">'.e($a->slug).'</p>')
            ->editColumn('type', fn (ProductAttribute $a) => $this->badge($a->type === 'color' ? 'Colour swatch' : 'Select', $a->type === 'color' ? 'violet' : 'sky'))
            ->addColumn('values', fn (ProductAttribute $a) => $this->blade(<<<'BLADE'
                <div class="flex max-w-xl flex-wrap gap-1.5">
                    @foreach ($values as $v)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                            @if ($v->color_hex)<span class="h-2.5 w-2.5 rounded-full ring-1 ring-slate-300" style="background: {{ $v->color_hex }}"></span>@endif
                            {{ $v->value }} <span class="text-slate-400">{{ $v->code }}</span>
                        </span>
                    @endforeach
                </div>
            BLADE, ['values' => $a->values]))
            ->addColumn('actions', fn (ProductAttribute $a) => $this->actions(
                edit: $user->can('update', $a) ? route('admin.attributes.edit', $a) : null,
                delete: $user->can('delete', $a) ? route('admin.attributes.destroy', $a) : null,
                confirm: "Delete attribute \"{$a->name}\"?",
            ))
            ->filterColumn('name', fn ($q, $k) => $q->where('attributes.name_en', 'like', $this->likeTerm($k)))
            ->orderColumn('name', 'attributes.name_en $1')
            ->rawColumns(['name', 'type', 'values', 'actions'])
            ->toJson();
    }
}
