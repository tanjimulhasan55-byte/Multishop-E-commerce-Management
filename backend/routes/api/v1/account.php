<?php

use App\Http\Controllers\Api\V1\Account\ChangePasswordController;
use App\Http\Controllers\Api\V1\Account\ListOrdersController;
use App\Http\Controllers\Api\V1\Account\PlaceOrderController;
use App\Http\Controllers\Api\V1\Account\ShowOrderController;
use App\Http\Controllers\Api\V1\Account\UpdateProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('account')->name('account.')->middleware(['auth:api', 'active'])->group(function () {
    Route::patch('profile', UpdateProfileController::class)->name('profile.update');
    Route::put('password', ChangePasswordController::class)->middleware('throttle:auth')->name('password.update');

    Route::get('orders', ListOrdersController::class)->name('orders.index');
    Route::post('orders', PlaceOrderController::class)->middleware('throttle:checkout')->name('orders.store');
    Route::get('orders/{order}', ShowOrderController::class)->name('orders.show');
});
