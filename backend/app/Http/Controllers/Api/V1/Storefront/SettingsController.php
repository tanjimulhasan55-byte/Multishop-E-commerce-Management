<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @tags Storefront
 */
class SettingsController extends Controller
{
    /**
     * Public storefront settings (enabled payment methods, locales, currency).
     *
     * @unauthenticated
     */
    public function __invoke(PaymentGateway $gateway): JsonResponse
    {
        $methods = collect(PaymentMethod::cases())
            ->filter(fn (PaymentMethod $m) => $m !== PaymentMethod::SslCommerz || $gateway->isEnabled())
            ->map(fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()])
            ->values();

        return response()->json(['meta' => [
            'currency' => 'BDT',
            'locales' => config('app.supported_locales'),
            'payment_methods' => $methods,
        ]]);
    }
}
