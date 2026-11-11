<?php

use App\Http\Controllers\Api\V1\Payments\InitiateSslCommerzPaymentController;
use App\Http\Controllers\Api\V1\Payments\SslCommerzCallbackController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->name('payments.')->group(function () {
    Route::post('orders/{order}/sslcommerz', InitiateSslCommerzPaymentController::class)
        ->middleware(['auth:api', 'active', 'throttle:checkout'])
        ->name('sslcommerz.initiate');

    // Called by SSLCommerz (browser POST-back and server IPN). Unauthenticated
    // by nature: every "success" is re-validated server-to-server.
    foreach (['success', 'fail', 'cancel', 'ipn'] as $outcome) {
        Route::match(['get', 'post'], "sslcommerz/{$outcome}", SslCommerzCallbackController::class)
            ->defaults('outcome', $outcome)
            ->name("sslcommerz.{$outcome}");
    }
});
