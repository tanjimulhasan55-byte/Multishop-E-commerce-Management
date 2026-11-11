<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersCells;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ShopsDataTable
{
    use RendersCells;

    public function toJson(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Shop::query()
            ->select('shops.*')
            ->with(['owner', 'media'])
            ->withCount('listings')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')));

        return DataTables::eloquent($query)
            ->addColumn('shop', fn (Shop $s) => $this->media($s->thumbnailUrls()['thumb'] ?? null, $s->name, $s->slug, 'store'))
            ->addColumn('owner', fn (Shop $s) => $s->owner
                ? '<p class="font-medium text-slate-800">'.e($s->owner->name).'</p><p class="text-xs text-slate-500">'.e($s->owner->email).'</p>'
                : '<span class="text-slate-400">Unassigned</span>')
            ->editColumn('status', fn (Shop $s) => $this->badge($s->status->label(), $s->status->color()))
            ->editColumn('listings_count', fn (Shop $s) => '<span class="font-semibold tabular-nums">'.$s->listings_count.'</span>')
            ->editColumn('created_at', fn (Shop $s) => $this->date($s->created_at))
            ->addColumn('actions', fn (Shop $s) => $this->actions(
                edit: $user->can('update', $s) ? route('admin.shops.edit', $s) : null,
                delete: $user->can('delete', $s) ? route('admin.shops.destroy', $s) : null,
                confirm: "Delete shop \"{$s->name}\"?",
            ))
            ->filterColumn('shop', fn ($q, $k) => $q->where(fn ($w) => $w->where('shops.name_en', 'like', $this->likeTerm($k))->orWhere('shops.slug', 'like', $this->likeTerm($k))))
            ->filterColumn('owner', fn ($q, $k) => $q->whereHas('owner', fn ($o) => $o->where('name', 'like', $this->likeTerm($k))->orWhere('email', 'like', $this->likeTerm($k))))
            ->orderColumn('shop', 'shops.name_en $1')
            ->rawColumns(['shop', 'owner', 'status', 'listings_count', 'created_at', 'actions'])
            ->toJson();
    }
}
