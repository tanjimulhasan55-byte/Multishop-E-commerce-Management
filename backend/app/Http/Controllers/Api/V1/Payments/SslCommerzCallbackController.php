<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Actions\Payments\HandlePaymentCallbackAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @tags Payments
 */
class SslCommerzCallbackController extends Controller
{
    /**
     * Browser return (success / fail / cancel) and server IPN from SSLCommerz.
     * The outcome comes from the route; a "success" is only accepted after
     * server-to-server validation with SSLCommerz.
     *
     * @unauthenticated
     */
    public function __invoke(Request $request, HandlePaymentCallbackAction $action, string $outcome): RedirectResponse|JsonResponse
    {
        $isIpn = $outcome === 'ipn';

        $payment = $action->execute($request->post(), $isIpn ? $this->ipnOutcome($request) : $outcome);

        if ($isIpn) {
            return response()->json(['received' => true]);
        }

        $orderNumber = $payment?->order?->number;
        $target = config('services.frontend_url').($orderNumber ? "/account/orders/{$orderNumber}" : '/account/orders');

        return redirect()->away($target.'?payment='.($payment?->status->value ?? 'failed'));
    }

    private function ipnOutcome(Request $request): string
    {
        return match (strtoupper((string) $request->post('status'))) {
            'VALID', 'VALIDATED' => 'success',
            'CANCELLED' => 'cancel',
            default => 'fail',
        };
    }
}
