<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersCells;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductsDataTable
{
    use RendersCells;

    public function toJson(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Product::query()
            ->select('products.*')
            ->with(['media', 'category', 'creator'])
            ->withCount(['listings', 'variants'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->input('assignment') === 'unassigned', fn ($q) => $q->doesntHave('listings'))
            ->when($request->input('assignment') === 'assigned', fn ($q) => $q->has('listings'));

        return DataTables::eloquent($query)
            ->addColumn('product', fn (Product $p) => $this->media($p->thumbnailUrls()['thumb'] ?? null, $p->name, $p->category?->name ?? 'Uncategorised', 'cube'))
            ->editColumn('sku', fn (Product $p) => '<code class="whitespace-nowrap rounded-md bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700">'.e($p->sku).'</code>')
            ->addColumn('variants', fn (Product $p) => $p->variants_count > 1
                ? $this->badge($p->variants_count.' variants', 'violet')
                : '<span class="text-xs text-slate-400">Simple</span>')
            ->editColumn('base_price', fn (Product $p) => $this->money($p->base_price))
            ->editColumn('status', fn (Product $p) => $this->badge($p->status->label(), $p->status->color()))
            ->addColumn('shops', fn (Product $p) => $p->listings_count > 0
                ? '<span class="font-semibold tabular-nums">'.$p->listings_count.'</span> <span class="text-xs text-slate-500">shops</span>'
                : $this->badge('Not listed', 'amber'))
            ->addColumn('creator', fn (Product $p) => e($p->creator?->name ?? '—'))
            ->addColumn('actions', fn (Product $p) => $this->actions(
                edit: $user->can('update', $p) ? route('admin.products.edit', $p) : null,
                delete: $user->can('delete', $p) ? route('admin.products.destroy', $p) : null,
                confirm: "Delete \"{$p->name}\" from the catalog?",
            ))
            ->filterColumn('product', fn ($q, $k) => $q->where(fn ($w) => $w->where('products.name_en', 'like', $this->likeTerm($k))
                ->orWhere('products.sku', 'like', $this->likeTerm($k))
                ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', $this->likeTerm($k)))))
            ->filterColumn('creator', fn ($q, $k) => $q->whereHas('creator', fn ($c) => $c->where('name', 'like', $this->likeTerm($k))))
            ->orderColumn('product', 'products.name_en $1')
            ->orderColumn('shops', 'listings_count $1')
            ->rawColumns(['product', 'sku', 'variants', 'base_price', 'status', 'shops', 'actions'])
            ->toJson();
    }
}
