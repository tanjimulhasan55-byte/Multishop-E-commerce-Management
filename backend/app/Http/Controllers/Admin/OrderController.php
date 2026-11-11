<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\UpdateOrderStatusAction;
use App\DataTables\OrdersDataTable;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Order::class);

        return view('admin.orders.index', [
            'statuses' => OrderStatus::options(),
            'paymentStatuses' => PaymentStatus::options(),
            'shops' => Shop::withTrashed()->orderBy('name_en')->get()->mapWithKeys(fn (Shop $s) => [$s->id => $s->name])->all(),
        ]);
    }

    public function data(Request $request, OrdersDataTable $table): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        return $table->toJson($request);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.show', ['order' => $order->load(['items', 'shop', 'customer', 'payments'])]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, UpdateOrderStatusAction $action): RedirectResponse
    {
        $action->execute($order, $request->status());

        return back()->with('success', "Order marked as {$order->status->label()}.");
    }
}
