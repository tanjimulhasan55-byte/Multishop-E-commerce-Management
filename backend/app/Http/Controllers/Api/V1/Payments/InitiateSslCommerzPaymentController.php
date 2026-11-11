<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Actions\Payments\InitiatePaymentAction;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * @tags Payments
 */
class InitiateSslCommerzPaymentController extends Controller
{
    /** Start an SSLCommerz hosted checkout for your order; redirect the browser to `meta.redirect_url`. */
    public function __invoke(Order $order, InitiatePaymentAction $action): JsonResponse
    {
        $this->authorize('pay', $order);

        try {
            $initiation = $action->execute($order);
        } catch (RuntimeException $e) {
            Log::error('SSLCommerz initiation failed', ['order' => $order->number, 'error' => $e->getMessage()]);
            abort(502, __('The payment gateway is unavailable. Please try again shortly.'));
        }

        return response()->json(['meta' => [
            'redirect_url' => $initiation->redirectUrl,
            'transaction_id' => $initiation->transactionId,
        ]]);
    }
}
