<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public
    Route::post('auth/register', [V1\AuthController::class, 'register'])->middleware('throttle:api-auth');
    Route::post('auth/login', [V1\AuthController::class, 'login'])->middleware('throttle:api-auth');

    Route::middleware('throttle:api')->group(function () {
        Route::get('products', [V1\ProductController::class, 'index']);
        Route::get('products/{slug}', [V1\ProductController::class, 'show']);
        Route::get('products/{slug}/reviews', [V1\ReviewController::class, 'index']);
        Route::get('categories', [V1\CategoryController::class, 'index']);
        Route::get('categories/{slug}', [V1\CategoryController::class, 'show']);
        Route::get('shipping-methods', [V1\MetaController::class, 'shippingMethods']);
        Route::get('payment-methods', [V1\MetaController::class, 'paymentMethods']);
    });

    // Authenticated (Sanctum bearer token)
    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [V1\AuthController::class, 'logout']);
        Route::get('auth/me', [V1\ProfileController::class, 'show']);

        Route::get('profile', [V1\ProfileController::class, 'show']);
        Route::put('profile', [V1\ProfileController::class, 'update']);
        Route::put('profile/password', [V1\ProfileController::class, 'password']);

        Route::get('cart', [V1\CartController::class, 'show']);
        Route::post('cart/items', [V1\CartController::class, 'add']);
        Route::patch('cart/items/{item}', [V1\CartController::class, 'update']);
        Route::delete('cart/items/{item}', [V1\CartController::class, 'remove']);
        Route::post('cart/coupon', [V1\CartController::class, 'applyCoupon']);
        Route::delete('cart/coupon', [V1\CartController::class, 'removeCoupon']);

        Route::get('wishlist', [V1\WishlistController::class, 'index']);
        Route::post('wishlist/{product}', [V1\WishlistController::class, 'store']);
        Route::delete('wishlist/{product}', [V1\WishlistController::class, 'destroy']);

        Route::get('orders', [V1\OrderController::class, 'index']);
        Route::post('orders', [V1\OrderController::class, 'store'])->middleware('throttle:checkout');
        Route::get('orders/{orderNumber}', [V1\OrderController::class, 'show']);
        Route::post('orders/{orderNumber}/cancel', [V1\OrderController::class, 'cancel']);

        Route::post('products/{slug}/reviews', [V1\ReviewController::class, 'store']);
        Route::put('reviews/{review}', [V1\ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [V1\ReviewController::class, 'destroy']);
    });
});
