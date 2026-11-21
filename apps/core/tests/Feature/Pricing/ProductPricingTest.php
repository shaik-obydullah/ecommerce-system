<?php

declare(strict_types=1);

use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Models\Stock;
use App\Core\Pricing\Models\ExchangeRate;
use App\Core\Pricing\Services\ProductPricing;
use App\Core\Support\Money\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, array<string, int>>  $lots
 */
function makePricedProduct(array $lots): Product
{
    $product = Product::factory()->create();

    foreach ($lots as $lot) {
        Stock::factory()->create([
            'product_id' => $product->getKey(),
            'qty_on_hand' => $lot['qty_on_hand'],
            'qty_reserved' => $lot['qty_reserved'] ?? 0,
            'buy_price_amount' => $lot['buy_price_amount'],
            'sale_price_amount' => $lot['sale_price_amount'],
        ]);
    }

    return $product->refresh();
}

it('derives the highest sale price from in-stock lots only', function (): void {
    $product = makePricedProduct([
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_000],
        ['qty_on_hand' => 2, 'buy_price_amount' => 4_500, 'sale_price_amount' => 8_000],
        ['qty_on_hand' => 0, 'buy_price_amount' => 5_000, 'sale_price_amount' => 9_900],
    ]);

    expect($product->derivedSalePriceAmount())->toBe(8_000)
        ->and($product->derivedAvailableQty())->toBe(7)
        ->and($product->inStock())->toBeTrue();
});

it('treats fully reserved lots as out of stock', function (): void {
    $product = makePricedProduct([
        ['qty_on_hand' => 4, 'qty_reserved' => 4, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_000],
    ]);

    expect($product->derivedSalePriceAmount())->toBeNull()
        ->and($product->derivedAvailableQty())->toBe(0)
        ->and($product->inStock())->toBeFalse();
});

it('subtracts reservations from available quantity', function (): void {
    $product = makePricedProduct([
        ['qty_on_hand' => 5, 'qty_reserved' => 2, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_000],
    ]);

    expect($product->derivedAvailableQty())->toBe(3);
});

it('adds derived stock aggregates when the summary scope is used', function (): void {
    $product = makePricedProduct([
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_000],
        ['qty_on_hand' => 0, 'buy_price_amount' => 5_000, 'sale_price_amount' => 9_900],
    ]);

    $loaded = Product::query()->withStockSummary()->findOrFail($product->getKey());

    expect($loaded->derivedSalePriceAmount())->toBe(7_000)
        ->and($loaded->derivedAvailableQty())->toBe(5)
        ->and($loaded->stockValueAmount())->toBe(20_000);
});

it('returns the base-currency price without conversion', function (): void {
    $product = makePricedProduct([
        ['qty_on_hand' => 3, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_999],
    ]);

    $price = app(ProductPricing::class)->for($product, Currency::base());

    expect($price->price?->amount())->toBe(7_999)
        ->and($price->price?->currency()->code())->toBe('USD')
        ->and($price->inStock)->toBeTrue()
        ->and($price->purchasable)->toBeTrue();
});

it('converts the derived price into the requested currency', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
    ]);

    $product = makePricedProduct([
        ['qty_on_hand' => 3, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_999],
    ]);

    $price = app(ProductPricing::class)->for($product, Currency::fromCode('EUR'));

    expect($price->price?->amount())->toBe(7_359)
        ->and($price->price?->currency()->code())->toBe('EUR')
        ->and($price->inStock)->toBeTrue()
        ->and($price->purchasable)->toBeTrue();
});

it('marks an in-stock product as not purchasable when no fx rate exists', function (): void {
    $product = makePricedProduct([
        ['qty_on_hand' => 3, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_999],
    ]);

    $price = app(ProductPricing::class)->for($product, Currency::fromCode('GBP'));

    expect($price->price)->toBeNull()
        ->and($price->inStock)->toBeTrue()
        ->and($price->purchasable)->toBeFalse();
});

it('reports out-of-stock products as unavailable', function (): void {
    $product = makePricedProduct([
        ['qty_on_hand' => 0, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_999],
    ]);

    $price = app(ProductPricing::class)->for($product, Currency::base());

    expect($price->price)->toBeNull()
        ->and($price->inStock)->toBeFalse()
        ->and($price->purchasable)->toBeFalse();
});
