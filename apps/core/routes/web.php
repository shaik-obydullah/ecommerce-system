<?php

declare(strict_types=1);

use App\Http\Middleware\ShareStorefrontContext;
use App\Http\Storefront\CartController;
use App\Http\Storefront\CartDiscountController;
use App\Http\Storefront\CartItemController;
use App\Http\Storefront\CategoryController;
use App\Http\Storefront\HomeController;
use App\Http\Storefront\ProductController;
use App\Http\Storefront\SearchController;
use Illuminate\Support\Facades\Route;

if (config('ecommerce.storefront_mode') === 'blade') {
    Route::middleware(ShareStorefrontContext::class)->group(function (): void {
        Route::get('/', HomeController::class)->name('storefront.home');

        Route::get('/categories/{category:slug}', CategoryController::class)
            ->name('storefront.categories.show');

        Route::get('/products/{product:slug}', ProductController::class)
            ->name('storefront.products.show');

        Route::get('/search', SearchController::class)->name('storefront.search');

        Route::get('/cart', [CartController::class, 'show'])->name('storefront.cart.show');
        Route::post('/cart/new', [CartController::class, 'startNew'])->name('storefront.cart.new');
        Route::delete('/cart', [CartController::class, 'destroy'])->name('storefront.cart.destroy');

        Route::post('/cart/items', [CartItemController::class, 'store'])
            ->name('storefront.cart.items.store');
        Route::patch('/cart/items/{item:public_id}', [CartItemController::class, 'update'])
            ->name('storefront.cart.items.update');
        Route::delete('/cart/items/{item:public_id}', [CartItemController::class, 'destroy'])
            ->name('storefront.cart.items.destroy');

        Route::post('/cart/discounts', [CartDiscountController::class, 'store'])
            ->name('storefront.cart.discounts.store');
        Route::delete('/cart/discounts/{code}', [CartDiscountController::class, 'destroy'])
            ->name('storefront.cart.discounts.destroy');
    });
}
