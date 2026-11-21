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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // JSON test requests only include cookies when credentials are enabled,
    // matching a browser calling the API with fetch({ credentials: 'include' }).
    $this->withCredentials();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function createApiCartProduct(array $attributes, int $salePrice, int $quantity = 10): Product
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

function cartToken(TestResponse $response): string
{
    $cookie = $response->getCookie('cart_token', decrypt: false);

    expect($cookie)->not->toBeNull();

    return (string) $cookie?->getValue();
}

it('returns an empty cart without creating one', function (): void {
    $response = $this->getJson('/api/v1/cart');

    $response->assertOk()
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.item_count', 0)
        ->assertJsonPath('data.totals.subtotal.amount', 0)
        ->assertJsonPath('data.totals.grand_total.amount', 0)
        ->assertJsonPath('data.items', [])
        ->assertCookieMissing('cart_token');

    expect(Cart::query()->count())->toBe(0);
});

it('creates a guest cart and returns server-calculated totals', function (): void {
    $product = createApiCartProduct(['name' => 'Studio Headphones'], 7_999);

    $response = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->public_id,
        'quantity' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.currency', 'USD')
        ->assertJsonPath('data.item_count', 2)
        ->assertJsonPath('data.items.0.product.name', 'Studio Headphones')
        ->assertJsonPath('data.items.0.unit_price.amount', 7_999)
        ->assertJsonPath('data.items.0.subtotal.amount', 15_998)
        ->assertJsonPath('data.totals.subtotal.amount', 15_998)
        ->assertJsonPath('data.totals.discount_total.amount', 0)
        ->assertJsonPath('data.totals.grand_total.amount', 15_998)
        ->assertJsonPath('data.is_purchasable', true)
        ->assertJsonMissingPath('data.items.0.buy_price_amount')
        ->assertJsonMissingPath('data.items.0.stocks');

    $token = cartToken($response);

    expect(Cart::query()->where('user_id', null)->count())->toBe(1)
        ->and(Cart::query()->value('token_hash'))->toBe(hash('sha256', $token));

    $this->withUnencryptedCookie('cart_token', $token)
        ->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.item_count', 2)
        ->assertJsonPath('data.items.0.quantity', 2);
});

it('updates quantities and rejects a stale cart version', function (): void {
    $product = createApiCartProduct(['name' => 'Speaker'], 2_000);
    $add = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->public_id,
        'quantity' => 1,
    ])->assertOk();

    $token = cartToken($add);
    $itemId = $add->json('data.items.0.id');
    $version = $add->json('data.version');

    $this->withUnencryptedCookie('cart_token', $token)
        ->patchJson("/api/v1/cart/items/{$itemId}", [
            'quantity' => 3,
            'version' => $version + 5,
        ])
        ->assertStatus(409)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('type', 'https://ecommerce.local/problems/cart-version-conflict');

    $this->withUnencryptedCookie('cart_token', $token)
        ->patchJson("/api/v1/cart/items/{$itemId}", [
            'quantity' => 3,
            'version' => $version,
        ])
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 3)
        ->assertJsonPath('data.totals.subtotal.amount', 6_000);
});

it('locks the cart currency and only starts a new cart on request', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
        'fetched_at' => now(),
    ]);

    $product = createApiCartProduct(['name' => 'Lamp'], 1_000);
    $add = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->public_id,
        'quantity' => 1,
    ])->assertOk();

    $token = cartToken($add);

    $this->withUnencryptedCookie('cart_token', $token)
        ->getJson('/api/v1/cart?currency=EUR')
        ->assertStatus(409)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('type', 'https://ecommerce.local/problems/cart-currency-locked');

    $replace = $this->withUnencryptedCookie('cart_token', $token)
        ->postJson('/api/v1/cart', [
            'currency' => 'EUR',
            'replace' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.currency', 'EUR')
        ->assertJsonPath('data.item_count', 0)
        ->assertJsonPath('data.totals.subtotal.amount', 0);

    expect(Cart::query()->count())->toBe(1)
        ->and(Cart::query()->value('currency'))->toBe('EUR');

    cartToken($replace);
});

it('applies and removes a coupon server-side', function (): void {
    $product = createApiCartProduct(['name' => 'Desk'], 10_000);
    $coupon = Discount::factory()->percentage(1_000)->create(['code' => 'WELCOME10']);

    $add = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->public_id,
        'quantity' => 1,
    ])->assertOk();

    $token = cartToken($add);

    $this->withUnencryptedCookie('cart_token', $token)
        ->postJson('/api/v1/cart/discounts', ['code' => 'welcome10'])
        ->assertOk()
        ->assertJsonPath('data.discount.code', 'WELCOME10')
        ->assertJsonPath('data.discount.valid', true)
        ->assertJsonPath('data.totals.discount_total.amount', 1_000)
        ->assertJsonPath('data.totals.grand_total.amount', 9_000);

    $this->withUnencryptedCookie('cart_token', $token)
        ->deleteJson('/api/v1/cart/discounts/WELCOME10')
        ->assertOk()
        ->assertJsonPath('data.discount', null)
        ->assertJsonPath('data.totals.discount_total.amount', 0)
        ->assertJsonPath('data.totals.grand_total.amount', 10_000);

    expect($coupon->usage_count)->toBe(0);
});

it('rejects unknown coupons with a problem response', function (): void {
    $product = createApiCartProduct(['name' => 'Chair'], 5_000);
    $add = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->public_id,
        'quantity' => 1,
    ])->assertOk();

    $this->withUnencryptedCookie('cart_token', cartToken($add))
        ->postJson('/api/v1/cart/discounts', ['code' => 'NOPE'])
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('title', 'Validation failed')
        ->assertJsonPath('errors.code.0', 'This coupon code is not valid.');
});

it('rejects quantities beyond available stock', function (): void {
    $product = createApiCartProduct(['name' => 'Cable'], 1_000, quantity: 1);

    $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->public_id,
        'quantity' => 2,
    ])->assertStatus(422)
        ->assertJsonPath('errors.quantity.0', 'Only 1 unit(s) are available.');
});

it('does not expose another cart line', function (): void {
    $product = createApiCartProduct(['name' => 'Monitor'], 3_000);
    $add = $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->public_id,
        'quantity' => 1,
    ])->assertOk();

    $token = cartToken($add);
    $other = Cart::factory()->create();
    $otherItem = CartItem::factory()->create([
        'cart_id' => $other->getKey(),
        'product_id' => $product->getKey(),
        'quantity' => 1,
    ]);

    $this->withUnencryptedCookie('cart_token', $token)
        ->patchJson("/api/v1/cart/items/{$otherItem->public_id}", [
            'quantity' => 2,
        ])
        ->assertNotFound();
});

it('merges a guest cart onto the customer on the next authenticated request', function (): void {
    $product = createApiCartProduct(['name' => 'Keyboard'], 4_000);
    $user = User::factory()->create();

    $guestToken = 'guest-token-'.Str::random(32);
    $guestCart = Cart::factory()->create([
        'user_id' => null,
        'token_hash' => hash('sha256', $guestToken),
        'currency' => 'USD',
    ]);

    CartItem::factory()->create([
        'cart_id' => $guestCart->getKey(),
        'product_id' => $product->getKey(),
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->withUnencryptedCookie('cart_token', $guestToken)
        ->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.item_count', 2)
        ->assertCookieExpired('cart_token');

    $userCart = Cart::query()->where('user_id', $user->getKey())->first();

    expect($userCart)->not->toBeNull()
        ->and($userCart?->getKey())->toBe($guestCart->getKey())
        ->and($guestCart->fresh()?->token_hash)->toBeNull()
        ->and($userCart?->items()->count())->toBe(1)
        ->and((int) $userCart?->items()->sum('quantity'))->toBe(2);
});
