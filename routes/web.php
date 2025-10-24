<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DemoPriceController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/catalogo', [CatalogController::class, 'index']);
Route::get('/producto/{slug}', [CatalogController::class, 'show']);
Route::get('/demo-precios', [DemoPriceController::class, 'index'])->middleware(['auth']);
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/place', [CheckoutController::class, 'place'])->name('checkout.place');
    Route::post('/checkout/upload-voucher', [CheckoutController::class, 'uploadVoucher'])->name('checkout.voucher');
});



Route::middleware(['auth', 'permission:orders.view'])
    ->prefix('admin')->name('admin.')->group(function () {

        // Listado y export
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/export', [AdminOrderController::class, 'export'])->name('orders.export');

        // Acciones MASIVAS
        Route::post('/orders/bulk-status', [AdminOrderController::class, 'bulkStatus'])
            ->middleware('permission:orders.update')->name('orders.bulkStatus');

        Route::post('/orders/bulk-paystatus', [AdminOrderController::class, 'bulkPayStatus'])
            ->middleware('permission:payments.validate')->name('orders.bulkPayStatus');

        // 🔹 Acciones INDIVIDUALES (necesarias para show.blade.php)
        Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
            ->middleware('permission:orders.update')->name('orders.status');

        Route::post('/orders/{order}/paystatus', [AdminOrderController::class, 'updatePaymentStatus'])
            ->middleware('permission:payments.validate')->name('orders.paystatus');

        // Detalle
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    });
Route::middleware(['auth', 'permission:catalog.manage'])
    ->prefix('admin')->name('admin.')->group(function () {

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });
require __DIR__ . '/auth.php';
