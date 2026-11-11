<?php

use App\Enums\PermissionName as P;
use App\Http\Controllers\Api\V1\Catalog\DeleteProductController;
use App\Http\Controllers\Api\V1\Catalog\DeleteVariantController;
use App\Http\Controllers\Api\V1\Catalog\GenerateVariantsController;
use App\Http\Controllers\Api\V1\Catalog\ListAttributesController;
use App\Http\Controllers\Api\V1\Catalog\StoreProductController;
use App\Http\Controllers\Api\V1\Catalog\UpdateProductController;
use App\Http\Controllers\Api\V1\Catalog\UpdateVariantController;
use App\Http\Controllers\Api\V1\Shop\BrowseCatalogController;
use App\Http\Controllers\Api\V1\Shop\DashboardController;
use App\Http\Controllers\Api\V1\Shop\DeleteListingController;
use App\Http\Controllers\Api\V1\Shop\ListMyShopsController;
use App\Http\Controllers\Api\V1\Shop\ListShopListingsController;
use App\Http\Controllers\Api\V1\Shop\ListShopOrdersController;
use App\Http\Controllers\Api\V1\Shop\StoreListingController;
use App\Http\Controllers\Api\V1\Shop\UpdateListingController;
use App\Http\Controllers\Api\V1\UpdateOrderStatusController;
use Illuminate\Support\Facades\Route;

// Shop-owner panel. The group requires at least one owner permission;
// each endpoint then enforces its own policy (ownership included).
$ownerPermissions = implode('|', [
    P::CatalogBrowse->value,
    P::OwnProductsManage->value,
    P::OwnListingsManage->value,
    P::OwnOrdersManage->value,
]);

Route::prefix('shop')->name('shop.')->middleware(['auth:api', 'active', "permission:{$ownerPermissions}"])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('shops', ListMyShopsController::class)->name('shops.index');

    // Shared catalog
    Route::get('catalog', BrowseCatalogController::class)->name('catalog.index');
    Route::post('products', StoreProductController::class)->middleware('throttle:uploads')->name('products.store');
    Route::patch('products/{product}', UpdateProductController::class)->middleware('throttle:uploads')->name('products.update');
    Route::delete('products/{product}', DeleteProductController::class)->name('products.destroy');

    // Variants of products I created (Size × Color …, each with its own SKU)
    Route::get('attributes', ListAttributesController::class)->name('attributes.index');
    Route::post('products/{product}/variants/generate', GenerateVariantsController::class)->name('variants.generate');
    Route::patch('variants/{variant}', UpdateVariantController::class)->name('variants.update');
    Route::delete('variants/{variant}', DeleteVariantController::class)->name('variants.destroy');

    // Listings (products in my shops)
    Route::get('shops/{shop}/listings', ListShopListingsController::class)->name('listings.index');
    Route::post('shops/{shop}/listings', StoreListingController::class)->middleware('throttle:uploads')->name('listings.store');
    Route::patch('listings/{listing}', UpdateListingController::class)->middleware('throttle:uploads')->name('listings.update');
    Route::delete('listings/{listing}', DeleteListingController::class)->name('listings.destroy');

    // Orders for my shops
    Route::get('orders', ListShopOrdersController::class)->name('orders.index');
    Route::patch('orders/{order}/status', UpdateOrderStatusController::class)->name('orders.status');
});
