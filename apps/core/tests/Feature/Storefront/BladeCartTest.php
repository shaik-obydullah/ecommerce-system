<?php

declare(strict_types=1);

use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Catalog\Models\Category;
use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Models\Stock;
use App\Core\Pricing\Models\ExchangeRate;
use App\Core\Promotions\Models\Discount;
use App\Core\Support\Money\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function createBladeCartProduct(array $attributes, int $salePrice, int $quantity = 10): Product
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

function bladeCartToken(TestResponse $response): string
{
    $cookie = $response->getCookie('cart_token', decrypt: false);

    expect($cookie)->not->toBeNull();

    return (string) $cookie?->getValue();
}

it('shows an add-to-cart form on a purchasable product page', function (): void {
    $product = createBladeCartProduct([
        'name' => 'Studio Lamp',
        'slug' => 'studio-lamp',
    ], 2_500);

    $this->get(route('storefront.products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertSee('Add to cart')
        ->assertSee('name="product_id"', false)
        ->assertSee($product->public_id);
});

it('adds an item to a guest cart and renders server-calculated totals', function (): void {
    $product = createBladeCartProduct([
        'name' => 'Studio Lamp',
        'slug' => 'studio-lamp',
    ], 2_500);

    $response = $this->post(route('storefront.cart.items.store'), [
        'product_id' => $product->public_id,
        'quantity' => 2,
    ]);

    $response->assertRedirect();
    $token = bladeCartToken($response);

    $this->withUnencryptedCookie('cart_token', $token)
        ->get(route('storefront.cart.show'))
        ->assertOk()
        ->assertSee('Your cart')
        ->assertSee('Studio Lamp')
        ->assertSee('$50.00')
        ->assertSee('$25.00 each');
});

it('updates, applies a coupon to, and clears a cart', function (): void {
    $product = createBladeCartProduct([
        'name' => 'Task Chair',
        'slug' => 'task-chair',
    ], 10_000);
    $coupon = Discount::factory()->percentage(1_000)->create(['code' => 'WELCOME10']);

    $add = $this->post(route('storefront.cart.items.store'), [
        'product_id' => $product->public_id,
        'quantity' => 1,
    ])->assertRedirect();

    $token = bladeCartToken($add);
    $cart = Cart::query()->where('token_hash', hash('sha256', $token))->firstOrFail();
    $item = CartItem::query()->where('cart_id', $cart->getKey())->firstOrFail();

    $this->withUnencryptedCookie('cart_token', $token)
        ->patch(route('storefront.cart.items.update', ['item' => $item->public_id]), [
            'quantity' => 2,
            'version' => $cart->version,
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Cart updated.');

    $this->withUnencryptedCookie('cart_token', $token)
        ->post(route('storefront.cart.discounts.store'), ['code' => 'welcome10'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Coupon applied.');

    $this->withUnencryptedCookie('cart_token', $token)
        ->get(route('storefront.cart.show'))
        ->assertOk()
        ->assertSee('Coupon')
        ->assertSee('WELCOME10')
        ->assertSee('$180.00');

    $this->withUnencryptedCookie('cart_token', $token)
        ->delete(route('storefront.cart.discounts.destroy', ['code' => 'WELCOME10']))
        ->assertRedirect()
        ->assertSessionHas('status', 'Coupon removed.');

    $this->withUnencryptedCookie('cart_token', $token)
        ->delete(route('storefront.cart.destroy'))
        ->assertRedirect()
        ->assertSessionHas('status', 'Cart cleared.');

    expect(Cart::query()->count())->toBe(0);
});

it('shows validation feedback instead of adding unavailable stock', function (): void {
    $product = createBladeCartProduct([
        'name' => 'Last Cable',
        'slug' => 'last-cable',
    ], 1_000, quantity: 1);

    $this->post(route('storefront.cart.items.store'), [
        'product_id' => $product->public_id,
        'quantity' => 2,
    ])->assertSessionHasErrors('quantity');
});

it('explains the locked currency and starts a new cart', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
        'fetched_at' => now(),
    ]);

    $product = createBladeCartProduct([
        'name' => 'Wall Clock',
        'slug' => 'wall-clock',
    ], 4_000);

    $add = $this->post(route('storefront.cart.items.store'), [
        'product_id' => $product->public_id,
        'quantity' => 1,
    ])->assertRedirect();

    $token = bladeCartToken($add);

    $this->withUnencryptedCookie('cart_token', $token)
        ->get(route('storefront.cart.show', ['currency' => 'EUR']))
        ->assertOk()
        ->assertSee('This cart is priced in USD.')
        ->assertSee('Start a EUR cart');

    $replace = $this->withUnencryptedCookie('cart_token', $token)
        ->post(route('storefront.cart.new'), ['currency' => 'EUR'])
        ->assertRedirect(route('storefront.cart.show'));

    $newToken = bladeCartToken($replace);

    $this->withUnencryptedCookie('cart_token', $newToken)
        ->get(route('storefront.cart.show'))
        ->assertOk()
        ->assertSee('Your cart is empty')
        ->assertSee('EUR');
});
