<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DemoPriceController;
use App\Http\Controllers\CatalogController;

Route::get('/', function () {
    return view('welcome');
});
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

require __DIR__ . '/auth.php';
