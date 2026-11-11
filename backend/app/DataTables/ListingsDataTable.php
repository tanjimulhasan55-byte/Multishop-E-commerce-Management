<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersCells;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ListingsDataTable
{
    use RendersCells;

    public function toJson(Request $request): JsonResponse
    {
        $actor = $request->user();

        $query = ShopProduct::query()
            ->select('shop_products.*')
            ->with(['media', 'product.media', 'shop'])
            ->withCount('variants')
            ->when($request->filled('shop_id'), fn ($q) => $q->where('shop_id', $request->integer('shop_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->input('stock') === 'low', fn ($q) => $q->where('stock', '<', 5));

        return DataTables::eloquent($query)
            ->addColumn('product', fn (ShopProduct $l) => $this->media($l->displayImages()['thumb'] ?? null, $l->displayName(), $l->product?->sku, 'cube'))
            ->addColumn('shop', fn (ShopProduct $l) => e($l->shop?->name ?? '—'))
            ->editColumn('price', fn (ShopProduct $l) => ($l->variants_count > 1 ? '<span class="text-xs text-slate-400">from </span>' : '').$this->money($l->price))
            ->editColumn('stock', fn (ShopProduct $l) => $l->stock < 5
                ? $this->badge($l->stock.' left', $l->stock === 0 ? 'rose' : 'amber')
                : '<span class="tabular-nums">'.$l->stock.'</span>')
            ->editColumn('variants_count', fn (ShopProduct $l) => '<span class="tabular-nums">'.$l->variants_count.'</span>')
            ->addColumn('visibility', fn (ShopProduct $l) => $l->isVisibleToCustomers()
                ? $this->badge('Visible', 'emerald')
                : $this->badge('Hidden', 'slate'))
            ->addColumn('actions', fn (ShopProduct $l) => $this->actions(
                delete: $actor->can('delete', $l) ? route('admin.listings.destroy', $l) : null,
                confirm: 'Remove this product from the shop?',
            ))
            ->filterColumn('product', fn ($q, $k) => $q->whereHas('product', fn ($p) => $p->where('name_en', 'like', $this->likeTerm($k))->orWhere('sku', 'like', $this->likeTerm($k))))
            ->filterColumn('shop', fn ($q, $k) => $q->whereHas('shop', fn ($s) => $s->where('name_en', 'like', $this->likeTerm($k))))
            ->rawColumns(['product', 'price', 'stock', 'variants_count', 'visibility', 'actions'])
            ->toJson();
    }
}
