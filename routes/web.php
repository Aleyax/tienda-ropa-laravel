<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DemoPriceController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AddressController;

// Admin
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrderController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Catálogo / Carrito / Precios demo
|--------------------------------------------------------------------------
*/
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalogo');
Route::get('/producto/{slug}', [CatalogController::class, 'show']);
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/demo-precios', [DemoPriceController::class, 'index'])->middleware(['auth']);

/*
|--------------------------------------------------------------------------
| Dashboard / Perfil
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout',                  [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/place',           [CheckoutController::class, 'place'])->name('checkout.place');
    Route::post('/checkout/upload-voucher',  [CheckoutController::class, 'uploadVoucher'])->name('checkout.voucher');
    Route::get('/checkout/thanks/{order}', function (\App\Models\Order $order) {
        return view('checkout.thanks', compact('order'));
    })->name('checkout.thanks');
});

/*
|--------------------------------------------------------------------------
| Direcciones del usuario
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/addresses',                [AddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses',               [AddressController::class, 'store'])->name('addresses.store');
    Route::delete('/addresses/{address}',   [AddressController::class, 'destroy'])->name('addresses.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Nota: usa el middleware de permisos que ya configuraste con Spatie.
|       Todo va en UN solo grupo para evitar duplicados.
*/
Route::middleware(['auth', 'permission:orders.view'])
    ->prefix('admin')->name('admin.')
    ->group(function () {

        // -------- Pedidos: listado / export
        Route::get('/orders',                [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/export',         [OrderController::class, 'export'])->name('orders.export');

        // -------- Acciones MASIVAS
        Route::post('/orders/bulk-status',   [OrderController::class, 'bulkStatus'])
            ->middleware('permission:orders.update')->name('orders.bulkStatus');

        Route::post('/orders/bulk-paystatus', [OrderController::class, 'bulkPayStatus'])
            ->middleware('permission:payments.validate')->name('orders.bulkPayStatus');

        // -------- Acciones INDIVIDUALES
        Route::post('/orders/{order}/status',    [OrderController::class, 'updateStatus'])
            ->middleware('permission:orders.update')->name('orders.status');

        Route::post('/orders/{order}/paystatus', [OrderController::class, 'updatePaymentStatus'])
            ->middleware('permission:payments.validate')->name('orders.paystatus');

        // -------- Detalle (usa el show de OrderController)
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

        // -------- Liquidación de envío (métodos en AdminOrderController)
        Route::post('/orders/{order}/shipping-actual', [AdminOrderController::class, 'saveShippingActual'])
            ->middleware('permission:orders.update')->name('orders.shippingActual');

        Route::post('/orders/{order}/settlement/refund', [AdminOrderController::class, 'settlementRefund'])
            ->middleware('permission:payments.validate')->name('orders.settlement.refund');

        Route::post('/orders/{order}/settlement/charge', [AdminOrderController::class, 'settlementCharge'])
            ->middleware('permission:payments.validate')->name('orders.settlement.charge');
    });

require __DIR__ . '/auth.php';
