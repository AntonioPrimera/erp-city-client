<?php

use ERPClient\Http\Controllers\CartController;
use ERPClient\Http\Controllers\ERP\ReviewsController;
use ERPClient\Http\Controllers\PaymentMethodsController;
use ERPClient\Http\Controllers\ERP\AuthController;
use ERPClient\Http\Controllers\ERP\FavoritesController;
use ERPClient\Http\Controllers\ERP\OrdersController;
use ERPClient\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('erp')->name('erp.')->group(function () {
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('cart.index');
        Route::post('/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('/update', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
        Route::delete('/clear', [CartController::class, 'clear'])->name('cart.clear');
        Route::post('/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
        Route::delete('/coupon', [CartController::class, 'clearCoupon'])->name('cart.coupon.clear');
    });

    Route::post('/address/validate', [CartController::class, 'validateAddress'])->name('address.validate');
    Route::get('/payment-methods', [PaymentMethodsController::class, 'index'])->name('paymentMethods');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/checkout/success', [OrderController::class, 'checkoutSuccess'])->name('checkout.success');
    Route::get('/checkout/cancel', [OrderController::class, 'checkoutCancel'])->name('checkout.cancel');

    Route::prefix('auth')->group(function () {
        Route::get('/session', [AuthController::class, 'session'])->name('auth.session');
        Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::put('/me', [AuthController::class, 'update'])->name('auth.update');
    });

    Route::prefix('account')->group(function () {
        Route::get('/favorites', [FavoritesController::class, 'index'])->name('account.favorites.index');
        Route::post('/favorites/{product}/toggle', [FavoritesController::class, 'toggle'])->name('account.favorites.toggle');
        Route::get('/orders', [OrdersController::class, 'index'])->name('account.orders.index');
    });

    Route::post('/products/{product}/reviews', [ReviewsController::class, 'store'])->name('products.reviews.store');
});
