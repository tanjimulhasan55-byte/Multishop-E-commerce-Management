<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Orders\PlaceOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\PlaceOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use Illuminate\Http\JsonResponse;

/**
 * @tags Account
 */
class PlaceOrderController extends Controller
{
    /**
     * Checkout: place an order with one shop. Prices are taken from the
     * server, never from the request.
     */
    public function __invoke(PlaceOrderRequest $request, PlaceOrderAction $action): JsonResponse
    {
        $order = $action->execute($request->user(), $request->toDto());

        return (new OrderResource($order->load('shop')))->response()->setStatusCode(201);
    }
}
