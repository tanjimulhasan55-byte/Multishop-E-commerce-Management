<?php

namespace App\DataTables;

use App\DataTables\Concerns\RendersCells;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OrdersDataTable
{
    use RendersCells;

    public function toJson(Request $request): JsonResponse
    {
        $query = Order::query()
            ->select('orders.*')
            ->with(['shop', 'customer'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('shop_id'), fn ($q) => $q->where('shop_id', $request->integer('shop_id')));

        return DataTables::eloquent($query)
            ->editColumn('number', fn (Order $o) => '<a href="'.route('admin.orders.show', $o).'" class="font-mono text-sm font-semibold text-brand-600 hover:text-brand-700">'.e($o->number).'</a>')
            ->addColumn('customer', fn (Order $o) => '<p class="font-medium text-slate-800">'.e($o->customer_name).'</p><p class="text-xs text-slate-500">'.e($o->customer_phone).'</p>')
            ->addColumn('shop', fn (Order $o) => e($o->shop?->name ?? '—'))
            ->editColumn('total', fn (Order $o) => $this->money($o->total).'<p class="text-xs text-slate-500">'.$o->items_count.' item(s)</p>')
            ->editColumn('status', fn (Order $o) => $this->badge($o->status->label(), $o->status->color()))
            ->editColumn('payment_status', fn (Order $o) => $this->badge($o->payment_status->label(), $o->payment_status->color())
                .'<p class="mt-1 text-xs text-slate-500">'.e($o->payment_method->label()).'</p>')
            ->editColumn('placed_at', fn (Order $o) => '<span class="whitespace-nowrap text-slate-500">'.e($o->placed_at->format('d M Y, H:i')).'</span>')
            ->addColumn('actions', fn (Order $o) => $this->actions(show: route('admin.orders.show', $o)))
            ->filterColumn('customer', fn ($q, $k) => $q->where(fn ($w) => $w->where('customer_name', 'like', $this->likeTerm($k))->orWhere('customer_phone', 'like', $this->likeTerm($k))))
            ->rawColumns(['number', 'customer', 'total', 'status', 'payment_status', 'placed_at', 'actions'])
            ->toJson();
    }
}
