<?php

use App\Enums\PermissionName as P;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ListingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Breeze redirects to "dashboard" after auth flows.
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->middleware('auth')->name('dashboard');

/*
| Admin panel (Blade + Breeze session auth). Entry requires `dashboard.view`;
| each controller action then authorizes against its policy, so custom
| roles only reach the screens their permissions allow.
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'permission:'.P::DashboardView->value])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // DataTables (yajra) JSON endpoints
    Route::get('shops/data', [ShopController::class, 'data'])->name('shops.data');
    Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
    Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
    Route::get('attributes/data', [AttributeController::class, 'data'])->name('attributes.data');
    Route::get('listings/data', [ListingController::class, 'data'])->name('listings.data');
    Route::get('orders/data', [OrderController::class, 'data'])->name('orders.data');
    Route::get('users/data', [UserController::class, 'data'])->name('users.data');
    Route::get('roles/data', [RoleController::class, 'data'])->name('roles.data');

    Route::resource('shops', ShopController::class)->except('show');
    Route::resource('products', ProductController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('attributes', AttributeController::class)->except('show');
    Route::resource('users', UserController::class)->except('show');
    Route::resource('roles', RoleController::class)->except('show');

    Route::post('products/{product}/variants/generate', [ProductVariantController::class, 'generate'])->name('products.variants.generate');
    Route::patch('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');

    Route::get('listings', [ListingController::class, 'index'])->name('listings.index');
    Route::delete('listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
