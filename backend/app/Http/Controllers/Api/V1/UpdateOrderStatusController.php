<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\UpdateOrderStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;

/**
 * @tags Orders
 */
class UpdateOrderStatusController extends Controller
{
    /** Move an order along pending → processing → shipped → delivered (or cancel). */
    public function __invoke(UpdateOrderStatusRequest $request, Order $order, UpdateOrderStatusAction $action): OrderResource
    {
        return new OrderResource($action->execute($order, $request->status())->load(['items', 'shop']));
    }
}
