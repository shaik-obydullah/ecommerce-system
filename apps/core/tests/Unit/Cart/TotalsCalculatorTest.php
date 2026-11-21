<?php

declare(strict_types=1);

use App\Core\Cart\Data\CartTotals;
use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Cart\Services\CartManager;
use App\Core\Cart\Services\TotalsCalculator;
use App\Core\Cart\Support\EmptyCart;
use App\Core\Catalog\Models\Category;
use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Models\Stock;
use App\Core\Pricing\Models\ExchangeRate;
use App\Core\Promotions\Models\Discount;
use App\Core\Support\Money\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function createCartProduct(array $attributes, int $salePrice, int $quantity): Product
{
    $category = Category::factory()->create();

    $product = Product::factory()->create(array_merge([
        'category_id' => $category->getKey(),
        'is_active' => true,
    ], $attributes));

    Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => $quantity,
        'qty_reserved' => 0,
        'buy_price_amount' => max(1, intdiv($salePrice, 2)),
        'sale_price_amount' => $salePrice,
        'currency' => Currency::base()->code(),
    ]);

    return $product;
}

/**
 * @param  array<int, array{0: Product, 1: int}>  $lines
 */
function cartWithLines(array $lines, string $currency = 'USD'): Cart
{
    $cart = Cart::factory()->create(['currency' => $currency]);

    foreach ($lines as [$product, $quantity]) {
        CartItem::factory()->create([
            'cart_id' => $cart->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => $quantity,
        ]);
    }

    return $cart;
}

function totalsFor(Cart $cart): CartTotals
{
    return app(TotalsCalculator::class)->calculate($cart);
}

it('calculates line totals from current stock prices', function (): void {
    $headphones = createCartProduct(['name' => 'Headphones'], 2_000, 10);
    $speaker = createCartProduct(['name' => 'Speaker'], 1_000, 10);

    $cart = cartWithLines([[$headphones, 2], [$speaker, 3]]);
    $totals = totalsFor($cart);

    expect($totals->currency->code())->toBe('USD')
        ->and($totals->itemCount())->toBe(5)
        ->and($totals->subtotal->amount())->toBe(7_000)
        ->and($totals->discount->amount())->toBe(0)
        ->and($totals->shipping->amount())->toBe(0)
        ->and($totals->tax->amount())->toBe(0)
        ->and($totals->grandTotal->amount())->toBe(7_000)
        ->and($totals->isPurchasable)->toBeTrue()
        ->and($totals->lines)->toHaveCount(2)
        ->and($totals->lines[0]->unitPrice?->amount())->toBe(2_000)
        ->and($totals->lines[0]->subtotal->amount())->toBe(4_000)
        ->and($totals->lines[1]->subtotal->amount())->toBe(3_000);
});

it('allocates a percentage coupon across lines and preserves the exact total', function (): void {
    $first = createCartProduct(['name' => 'First'], 2_000, 10);
    $second = createCartProduct(['name' => 'Second'], 1_000, 10);

    $cart = cartWithLines([[$first, 2], [$second, 3]]);
    $coupon = Discount::factory()->percentage(1_000)->create(['code' => 'WELCOME10']);

    $cart->discount()->create([
        'discount_id' => $coupon->getKey(),
        'code' => $coupon->code,
    ]);

    $totals = totalsFor($cart->refresh());

    expect($totals->subtotal->amount())->toBe(7_000)
        ->and($totals->discount->amount())->toBe(700)
        ->and($totals->grandTotal->amount())->toBe(6_300)
        ->and($totals->lines[0]->discount->amount())->toBe(400)
        ->and($totals->lines[1]->discount->amount())->toBe(300)
        ->and($totals->lines[0]->total->amount())->toBe(3_600)
        ->and($totals->lines[1]->total->amount())->toBe(2_700)
        ->and($totals->discountQuote?->valid)->toBeTrue();
});

it('absorbs coupon rounding on the last line', function (): void {
    $first = createCartProduct(['name' => 'A'], 333, 10);
    $second = createCartProduct(['name' => 'B'], 333, 10);
    $third = createCartProduct(['name' => 'C'], 334, 10);

    $cart = cartWithLines([[$first, 1], [$second, 1], [$third, 1]]);
    $coupon = Discount::factory()->percentage(1_000)->create();

    $cart->discount()->create([
        'discount_id' => $coupon->getKey(),
        'code' => $coupon->code,
    ]);

    $totals = totalsFor($cart->refresh());
    $allocated = array_sum(array_map(
        static fn ($line): int => $line->discount->amount(),
        $totals->lines,
    ));

    expect($totals->discount->amount())->toBe(100)
        ->and($allocated)->toBe(100)
        ->and($totals->lines[0]->discount->amount())->toBe(33)
        ->and($totals->lines[1]->discount->amount())->toBe(33)
        ->and($totals->lines[2]->discount->amount())->toBe(34);
});

it('never discounts more than the subtotal', function (): void {
    $product = createCartProduct(['name' => 'Cheap'], 500, 10);
    $cart = cartWithLines([[$product, 1]]);
    $coupon = Discount::factory()->fixed(10_000)->create();

    $cart->discount()->create([
        'discount_id' => $coupon->getKey(),
        'code' => $coupon->code,
    ]);

    $totals = totalsFor($cart->refresh());

    expect($totals->discount->amount())->toBe(500)
        ->and($totals->grandTotal->amount())->toBe(0);
});

it('marks the cart unpurchasable when a coupon no longer qualifies', function (): void {
    $product = createCartProduct(['name' => 'Lamp'], 1_000, 10);
    $cart = cartWithLines([[$product, 1]]);
    $coupon = Discount::factory()
        ->percentage(1_000)
        ->create(['min_subtotal_amount' => 5_000]);

    $cart->discount()->create([
        'discount_id' => $coupon->getKey(),
        'code' => $coupon->code,
    ]);

    $totals = totalsFor($cart->refresh());

    expect($totals->discount->amount())->toBe(0)
        ->and($totals->discountQuote?->valid)->toBeFalse()
        ->and($totals->isPurchasable)->toBeFalse();
});

it('prices lines in the cart currency using the latest exchange rate', function (): void {
    $product = createCartProduct(['name' => 'Converted'], 1_000, 10);

    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
        'fetched_at' => now(),
    ]);

    $cart = cartWithLines([[$product, 1]], 'EUR');
    $totals = totalsFor($cart);

    expect($totals->currency->code())->toBe('EUR')
        ->and($totals->subtotal->amount())->toBe(920)
        ->and($totals->grandTotal->amount())->toBe(920);
});

it('returns an empty totals payload for a transient cart', function (): void {
    $totals = totalsFor(EmptyCart::make(Currency::fromCode('USD')));

    expect($totals->isEmpty())->toBeTrue()
        ->and($totals->itemCount())->toBe(0)
        ->and($totals->subtotal->amount())->toBe(0)
        ->and($totals->isPurchasable)->toBeFalse();
});

it('resolves a cart for a guest request using the cart manager', function (): void {
    $request = Request::create('/cart', 'GET');
    $manager = app(CartManager::class);

    $cart = $manager->resolve($request, Currency::fromCode('USD'));

    expect($cart->exists)->toBeTrue()
        ->and($cart->isGuest())->toBeTrue()
        ->and($manager->issuedToken())->not->toBeNull();
});
