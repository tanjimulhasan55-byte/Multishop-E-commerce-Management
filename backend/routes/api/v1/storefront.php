<?php

use App\Http\Controllers\Api\V1\Storefront\ListCategoriesController;
use App\Http\Controllers\Api\V1\Storefront\ListListingsController;
use App\Http\Controllers\Api\V1\Storefront\ListShopsController;
use App\Http\Controllers\Api\V1\Storefront\SettingsController;
use App\Http\Controllers\Api\V1\Storefront\ShowListingController;
use App\Http\Controllers\Api\V1\Storefront\ShowShopController;
use Illuminate\Support\Facades\Route;

// Public, read-only. Every query here goes through the customer
// visibility rules (active shop + active listing + active product).
Route::prefix('storefront')->name('storefront.')->group(function () {
    Route::get('settings', SettingsController::class)->name('settings');
    Route::get('categories', ListCategoriesController::class)->name('categories.index');
    Route::get('shops', ListShopsController::class)->name('shops.index');
    Route::get('shops/{slug}', ShowShopController::class)->where('slug', '[a-z0-9\-]+')->name('shops.show');
    Route::get('listings', ListListingsController::class)->name('listings.index');
    Route::get('listings/{listing}', ShowListingController::class)->whereNumber('listing')->name('listings.show');
});
