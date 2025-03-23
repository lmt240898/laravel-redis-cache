<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductFilterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('products.index');
});

Route::resource('products', ProductController::class);

Route::post('/products/{product}/purchase', [ProductController::class, 'purchase'])->name('products.purchase');

Route::get('/filter', [ProductFilterController::class, 'index'])->name('products.filter');
