<?php

use App\Enums\PermissionName as P;
use App\Http\Controllers\Api\V1\Admin\Orders\IndexOrdersController;
use App\Http\Controllers\Api\V1\Admin\Products\IndexProductsController;
use App\Http\Controllers\Api\V1\Admin\Products\ShowProductController;
use App\Http\Controllers\Api\V1\Admin\ShopOwners\DestroyShopOwnerController;
use App\Http\Controllers\Api\V1\Admin\ShopOwners\IndexShopOwnersController;
use App\Http\Controllers\Api\V1\Admin\ShopOwners\StoreShopOwnerController;
use App\Http\Controllers\Api\V1\Admin\ShopOwners\UpdateShopOwnerController;
use App\Http\Controllers\Api\V1\Admin\Shops\DestroyShopController;
use App\Http\Controllers\Api\V1\Admin\Shops\IndexShopsController;
use App\Http\Controllers\Api\V1\Admin\Shops\ShowShopController;
use App\Http\Controllers\Api\V1\Admin\Shops\StoreShopController;
use App\Http\Controllers\Api\V1\Admin\Shops\UpdateShopController;
use App\Http\Controllers\Api\V1\Catalog\DeleteProductController;
use App\Http\Controllers\Api\V1\Catalog\DeleteVariantController;
use App\Http\Controllers\Api\V1\Catalog\GenerateVariantsController;
use App\Http\Controllers\Api\V1\Catalog\ListAttributesController;
use App\Http\Controllers\Api\V1\Catalog\StoreProductController;
use App\Http\Controllers\Api\V1\Catalog\UpdateProductController;
use App\Http\Controllers\Api\V1\Catalog\UpdateVariantController;
use App\Http\Controllers\Api\V1\UpdateOrderStatusController;
use Illuminate\Support\Facades\Route;

// Admin API (the Blade panel at /admin is the primary admin UI).
// Each endpoint authorizes against its policy / permission.
Route::prefix('admin')->name('admin.')->middleware(['auth:api', 'active', 'permission:'.P::DashboardView->value])->group(function () {
    Route::get('shops', IndexShopsController::class)->name('shops.index');
    Route::post('shops', StoreShopController::class)->name('shops.store');
    Route::get('shops/{shop}', ShowShopController::class)->name('shops.show');
    Route::patch('shops/{shop}', UpdateShopController::class)->name('shops.update');
    Route::delete('shops/{shop}', DestroyShopController::class)->name('shops.destroy');

    Route::get('products', IndexProductsController::class)->name('products.index');
    Route::post('products', StoreProductController::class)->name('products.store');
    Route::get('products/{product}', ShowProductController::class)->name('products.show');
    Route::patch('products/{product}', UpdateProductController::class)->name('products.update');
    Route::delete('products/{product}', DeleteProductController::class)->name('products.destroy');
    Route::post('products/{product}/variants/generate', GenerateVariantsController::class)->name('variants.generate');
    Route::patch('variants/{variant}', UpdateVariantController::class)->name('variants.update');
    Route::delete('variants/{variant}', DeleteVariantController::class)->name('variants.destroy');
    Route::get('attributes', ListAttributesController::class)->name('attributes.index');

    Route::get('shop-owners', IndexShopOwnersController::class)->name('shop-owners.index');
    Route::post('shop-owners', StoreShopOwnerController::class)->name('shop-owners.store');
    Route::patch('shop-owners/{user}', UpdateShopOwnerController::class)->name('shop-owners.update');
    Route::delete('shop-owners/{user}', DestroyShopOwnerController::class)->name('shop-owners.destroy');

    Route::get('orders', IndexOrdersController::class)->name('orders.index');
    Route::patch('orders/{order}/status', UpdateOrderStatusController::class)->name('orders.status');
});
