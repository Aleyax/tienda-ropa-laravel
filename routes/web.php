<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DemoPriceController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\PickBasketController;
// Admin
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrderController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'permission:settings.update'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
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
Route::middleware(['auth'])
    ->prefix('admin')->name('admin.')
    ->group(function () {

        // routes/web.php (dentro del grupo admin)

        Route::get('/baskets/transfers', [PickBasketController::class, 'transfersIndex'])
            ->name('baskets.transfers');

        Route::post('/orders/{order}/basket/open',   [PickBasketController::class, 'open'])
            ->middleware('permission:orders.update')
            ->name('orders.basket.open');

        Route::post('/baskets/{basket}/pick',       [PickBasketController::class, 'pick'])
            ->middleware('permission:orders.update')
            ->name('baskets.pick');

        Route::post('/baskets/{basket}/unpick',     [PickBasketController::class, 'unpick'])
            ->middleware('permission:orders.update')
            ->name('baskets.unpick');

        Route::post('/baskets/{basket}/close',      [PickBasketController::class, 'close'])
            ->middleware('permission:orders.update')
            ->name('baskets.close');

        // Transferencias de canasta
        Route::post('/baskets/{basket}/transfer',   [PickBasketController::class, 'transferCreate'])
            ->middleware('permission:orders.update')
            ->name('baskets.transfer.create');

        Route::post('/basket-transfers/{transfer}/accept', [PickBasketController::class, 'transferAccept'])
            ->middleware('permission:orders.update')
            ->name('baskets.transfer.accept');

        Route::post('/basket-transfers/{transfer}/decline', [PickBasketController::class, 'transferDecline'])
            ->middleware('permission:orders.update')
            ->name('baskets.transfer.decline');

        Route::post('/basket-transfers/{transfer}/cancel', [PickBasketController::class, 'transferCancel'])
            ->middleware('permission:orders.update')
            ->name('baskets.transfer.cancel');

        // Búsqueda de usuarios activos para transferir canastas (autocomplete)
        Route::get('/users/search', [PickBasketController::class, 'userLookup'])
            ->middleware('permission:orders.update')
            ->name('users.search');



        Route::post('/orders/{order}/priority', [OrderController::class, 'updatePriority'])
            ->middleware('permission:orders.update')
            ->name('orders.priority');


        Route::post('/orders/{order}/items/{item}/pick', [OrderController::class, 'pickItem'])
            ->middleware('permission:orders.update')
            ->name('orders.items.pick');

        Route::post('/orders/{order}/items/{item}/unpick', [OrderController::class, 'unpickItem'])
            ->middleware('permission:orders.update')
            ->name('orders.items.unpick');
        // -------- Pedidos (con permisos propios)
        Route::middleware('permission:orders.view')->group(function () {
            Route::get('/orders',                [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/export',         [OrderController::class, 'export'])->name('orders.export');
            Route::get('/orders/{order}',        [OrderController::class, 'show'])->name('orders.show');
        });

        Route::post('/orders/bulk-status',      [OrderController::class, 'bulkStatus'])
            ->middleware('permission:orders.update')->name('orders.bulkStatus');

        Route::post('/orders/bulk-paystatus',   [OrderController::class, 'bulkPayStatus'])
            ->middleware('permission:payments.validate')->name('orders.bulkPayStatus');

        Route::post('/orders/{order}/status',   [OrderController::class, 'updateStatus'])
            ->middleware('permission:orders.update')->name('orders.status');

        Route::post('/orders/{order}/paystatus', [OrderController::class, 'updatePaymentStatus'])
            ->middleware('permission:payments.validate')->name('orders.paystatus');

        // Liquidación de envío
        Route::post('/orders/{order}/shipping-actual', [AdminOrderController::class, 'saveShippingActual'])
            ->middleware('permission:orders.update')->name('orders.shippingActual');

        Route::post('/orders/{order}/settlement/refund', [AdminOrderController::class, 'settlementRefund'])
            ->middleware('permission:payments.validate')->name('orders.settlement.refund');

        Route::post('/orders/{order}/settlement/charge', [AdminOrderController::class, 'settlementCharge'])
            ->middleware('permission:payments.validate')->name('orders.settlement.charge');

        /* ============================
        |  Productos (permisos propios)
        ============================ */
        Route::middleware('permission:products.view')->group(function () {
            Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        });

        Route::middleware('permission:products.create')->group(function () {
            Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('/products',        [ProductController::class, 'store'])->name('products.store');
        });

        Route::middleware('permission:products.update')->group(function () {
            Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('/products/{product}',       [ProductController::class, 'update'])->name('products.update');

            Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('variants.store');
            Route::put('/variants/{variant}',           [ProductVariantController::class, 'update'])->name('variants.update');
            Route::delete('/variants/{variant}',        [ProductVariantController::class, 'destroy'])->name('variants.destroy');

            Route::post('/products/{product}/media', [MediaController::class, 'store'])->name('media.store');
            Route::delete('/media/{media}',          [MediaController::class, 'destroy'])->name('media.destroy');
        });

        Route::delete('/products/{product}', [ProductController::class, 'destroy'])
            ->name('products.destroy')
            ->middleware('permission:products.delete');
    });


require __DIR__ . '/auth.php';
