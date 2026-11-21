<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\Cart\CartDiscountController;
use App\Http\Controllers\Api\V1\Cart\CartItemController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use App\Http\Controllers\Api\V1\Catalog\ProductController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\VersionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware('throttle:api')
    ->group(function (): void {
        Route::get('/health', HealthController::class)->name('api.v1.health');
        Route::get('/version', VersionController::class)->name('api.v1.version');

        Route::get('/categories', [CategoryController::class, 'index'])
            ->name('api.v1.categories.index');
        Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])
            ->name('api.v1.categories.show');

        Route::get('/products', [ProductController::class, 'index'])
            ->name('api.v1.products.index');
        Route::get('/products/{product:slug}', [ProductController::class, 'show'])
            ->name('api.v1.products.show');

        Route::middleware('throttle:cart')->group(function (): void {
            Route::get('/cart', [CartController::class, 'show'])->name('api.v1.cart.show');
            Route::post('/cart', [CartController::class, 'store'])->name('api.v1.cart.store');
            Route::delete('/cart', [CartController::class, 'destroy'])->name('api.v1.cart.destroy');

            Route::post('/cart/items', [CartItemController::class, 'store'])->name('api.v1.cart.items.store');
            Route::patch('/cart/items/{item}', [CartItemController::class, 'update'])->name('api.v1.cart.items.update');
            Route::delete('/cart/items/{item}', [CartItemController::class, 'destroy'])->name('api.v1.cart.items.destroy');

            Route::post('/cart/discounts', [CartDiscountController::class, 'store'])
                ->name('api.v1.cart.discounts.store');
            Route::delete('/cart/discounts/{code}', [CartDiscountController::class, 'destroy'])
                ->name('api.v1.cart.discounts.destroy');
        });
    });
