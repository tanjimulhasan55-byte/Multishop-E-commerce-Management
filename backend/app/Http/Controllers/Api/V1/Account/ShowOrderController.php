<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;

/**
 * @tags Account
 */
class ShowOrderController extends Controller
{
    public function __invoke(Order $order): OrderResource
    {
        $this->authorize('view', $order);

        return new OrderResource($order->load(['items', 'shop']));
    }
}
