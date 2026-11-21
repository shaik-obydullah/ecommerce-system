<?php

declare(strict_types=1);

use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Enums\StockMovementType;
use App\Core\Inventory\Models\Stock;
use App\Core\Inventory\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates stock value from on-hand quantity and buy price', function (): void {
    $product = Product::factory()->create();

    $stock = Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => 5,
        'qty_reserved' => 2,
        'buy_price_amount' => 4_200,
        'sale_price_amount' => 7_999,
    ]);

    expect($stock->stockValueAmount())->toBe(21_000)
        ->and($stock->qtyAvailable())->toBe(3)
        ->and($product->stockValueAmount())->toBe(21_000);
});

it('sums stock value across lots and ignores soft-deleted lots', function (): void {
    $product = Product::factory()->create();

    Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => 5,
        'buy_price_amount' => 4_000,
        'sale_price_amount' => 7_000,
    ]);

    $deleted = Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => 2,
        'buy_price_amount' => 5_000,
        'sale_price_amount' => 9_000,
    ]);

    $deleted->delete();

    expect($product->stockValueAmount())->toBe(20_000);
});

it('records a purchase movement when a stock lot is created', function (): void {
    $stock = Stock::factory()->create([
        'qty_on_hand' => 7,
        'buy_price_amount' => 4_000,
        'sale_price_amount' => 7_000,
    ]);

    $movement = StockMovement::query()
        ->where('stock_id', $stock->getKey())
        ->sole();

    expect($movement->type)->toBe(StockMovementType::Purchase)
        ->and($movement->quantity)->toBe(7)
        ->and($movement->product_id)->toBe($stock->product_id)
        ->and($movement->unit_buy_price_amount)->toBe(4_000)
        ->and($movement->unit_sale_price_amount)->toBe(7_000);
});

it('does not record a purchase movement for an empty lot', function (): void {
    $stock = Stock::factory()->outOfStock()->create();

    expect(StockMovement::query()->where('stock_id', $stock->getKey())->count())->toBe(0);
});

it('records an adjustment movement when quantity changes', function (): void {
    $stock = Stock::factory()->create([
        'qty_on_hand' => 10,
        'buy_price_amount' => 4_000,
        'sale_price_amount' => 7_000,
    ]);

    $stock->update(['qty_on_hand' => 6]);

    $adjustment = StockMovement::query()
        ->where('stock_id', $stock->getKey())
        ->where('type', StockMovementType::Adjustment)
        ->sole();

    expect($adjustment->quantity)->toBe(-4);
});

it('records a price change movement with the previous prices', function (): void {
    $stock = Stock::factory()->create([
        'qty_on_hand' => 10,
        'buy_price_amount' => 4_000,
        'sale_price_amount' => 7_000,
    ]);

    $stock->update([
        'buy_price_amount' => 4_500,
        'sale_price_amount' => 8_000,
    ]);

    $movement = StockMovement::query()
        ->where('stock_id', $stock->getKey())
        ->where('type', StockMovementType::PriceChange)
        ->sole();

    expect($movement->quantity)->toBe(0)
        ->and($movement->metadata)->toMatchArray([
            'previous_buy_price_amount' => 4_000,
            'previous_sale_price_amount' => 7_000,
        ]);
});

it('records a removal adjustment when a stock lot is deleted', function (): void {
    $stock = Stock::factory()->create([
        'qty_on_hand' => 4,
        'buy_price_amount' => 4_000,
        'sale_price_amount' => 7_000,
    ]);

    $stock->delete();

    $movement = StockMovement::query()
        ->where('stock_id', $stock->getKey())
        ->where('type', StockMovementType::Adjustment)
        ->sole();

    expect($movement->quantity)->toBe(-4)
        ->and($movement->metadata)->toMatchArray(['reason' => 'stock lot deleted'])
        ->and($stock->fresh()?->trashed())->toBeTrue()
        ->and(Stock::query()->find($stock->getKey()))->toBeNull();
});
