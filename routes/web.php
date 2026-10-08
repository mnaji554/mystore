<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminInvoiceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutResultController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Account;
use App\Livewire\Admin;
use App\Livewire\CartPage;
use App\Livewire\Checkout;
use App\Livewire\Home;
use App\Livewire\ProductListing;
use App\Livewire\ProductShow;
use App\Livewire\WishlistPage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/
Route::get('/', Home::class)->name('home');

Route::get('/products', ProductListing::class)->defaults('mode', 'all')->name('products.index');
Route::get('/products/new', ProductListing::class)->defaults('mode', 'new')->name('products.new');
Route::get('/products/bestsellers', ProductListing::class)->defaults('mode', 'bestsellers')->name('products.bestsellers');
Route::get('/products/sale', ProductListing::class)->defaults('mode', 'sale')->name('products.sale');
Route::get('/products/{slug}', ProductShow::class)->name('products.show');
Route::get('/categories/{slug}', ProductListing::class)->defaults('mode', 'category')->name('categories.show');
Route::get('/search', ProductListing::class)->defaults('mode', 'search')->name('search');

Route::get('/cart', CartPage::class)->name('cart');
Route::get('/checkout', Checkout::class)->name('checkout');

Route::get('/checkout/success/{number}', [CheckoutResultController::class, 'success'])->name('checkout.success');
Route::get('/checkout/stripe/return/{number}', [CheckoutResultController::class, 'stripeReturn'])->name('checkout.stripe.return');
Route::get('/checkout/payment-cancelled/{number}', [CheckoutResultController::class, 'cancelled'])->name('checkout.payment-cancelled');
Route::post('/checkout/retry-payment/{number}', [CheckoutResultController::class, 'retryPayment'])->middleware('throttle:checkout')->name('checkout.retry');
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::get('/coupons', [PageController::class, 'coupons'])->name('coupons');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'sendContact'])->middleware('throttle:forms')->name('contact.send');
Route::get('/track-order', [PageController::class, 'trackForm'])->name('orders.track');
Route::post('/track-order', [PageController::class, 'track'])->middleware('throttle:forms')->name('orders.track.search');

Route::prefix('pages')->name('pages.')->group(function () {
    foreach (['about', 'privacy', 'terms', 'returns'] as $page) {
        Route::get($page, fn () => view('pages.'.$page))->name($page);
    }
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:forms');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:forms')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:forms')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Customer account
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/wishlist', WishlistPage::class)->name('wishlist');

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
        Route::get('/orders/{orderNumber}', [AccountController::class, 'showOrder'])->name('orders.show');
        Route::post('/orders/{orderNumber}/cancel', [AccountController::class, 'cancelOrder'])->name('orders.cancel');
        Route::get('/orders/{orderNumber}/invoice', [AccountController::class, 'invoice'])->name('orders.invoice');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/password', [AccountController::class, 'password'])->name('password');
        Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.update');
        Route::get('/addresses', Account\Addresses::class)->name('addresses');
        Route::get('/reviews', Account\Reviews::class)->name('reviews');
    });
});

/*
|--------------------------------------------------------------------------
| Admin dashboard (staff only; every Livewire action re-authorizes)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', Admin\Dashboard::class)->name('dashboard');
    Route::get('/products', Admin\ProductIndex::class)->name('products.index');
    Route::get('/products/create', Admin\ProductForm::class)->name('products.create');
    Route::get('/products/{product}/edit', Admin\ProductForm::class)->name('products.edit');
    Route::get('/categories', Admin\Categories::class)->name('categories');
    Route::get('/orders', Admin\OrderIndex::class)->name('orders.index');
    Route::get('/orders/{order}', Admin\OrderShow::class)->name('orders.show');
    Route::get('/orders/{order}/invoice', [AdminInvoiceController::class, 'show'])->name('orders.invoice');
    Route::get('/orders/{order}/invoice.pdf', [AdminInvoiceController::class, 'pdf'])->name('orders.invoice.pdf');
    Route::get('/customers', Admin\CustomerIndex::class)->name('customers.index');
    Route::get('/customers/{user}', Admin\CustomerShow::class)->name('customers.show')->withTrashed();
    Route::get('/coupons', Admin\Coupons::class)->name('coupons');
    Route::get('/shipping', Admin\Shipping::class)->name('shipping');
    Route::get('/reviews', Admin\Reviews::class)->name('reviews');
    Route::get('/messages', Admin\Messages::class)->name('messages');
    Route::get('/settings', Admin\Settings::class)->name('settings');
    Route::get('/notifications', Admin\Notifications::class)->name('notifications');
});
