<?php

declare(strict_types=1);

use App\Core\Catalog\Models\Category;
use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Models\Stock;
use App\Core\Pricing\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, array<string, mixed>>  $lots
 * @param  array<string, mixed>  $attributes
 */
function createBladeProduct(Category $category, array $attributes, array $lots): Product
{
    $product = Product::factory()->create(array_merge([
        'category_id' => $category->getKey(),
        'is_active' => true,
        'is_featured' => false,
    ], $attributes));

    foreach ($lots as $lot) {
        Stock::factory()->create(array_merge([
            'product_id' => $product->getKey(),
            'qty_reserved' => 0,
        ], $lot));
    }

    return $product;
}

it('renders the home page with featured and latest products', function (): void {
    $category = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);

    createBladeProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
        'is_featured' => true,
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_200, 'sale_price_amount' => 7_999],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('One price. Every product.')
        ->assertSee('Studio Wireless Headphones')
        ->assertSee('Audio')
        ->assertSee('$79.99')
        ->assertDontSee('$42.00')
        ->assertDontSee('LOT-');
});

it('renders only the products in the requested category', function (): void {
    $audio = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);
    $home = Category::factory()->create(['name' => 'Home', 'slug' => 'home']);

    createBladeProduct($audio, ['name' => 'Studio Headphones', 'slug' => 'studio-headphones'], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_000],
    ]);

    createBladeProduct($home, ['name' => 'Arc Desk Lamp', 'slug' => 'arc-desk-lamp'], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 3_000, 'sale_price_amount' => 7_499],
    ]);

    $this->get('/categories/audio')
        ->assertOk()
        ->assertSee('Studio Headphones')
        ->assertDontSee('Arc Desk Lamp');
});

it('shows one derived price and hides stock lots and buy prices', function (): void {
    $category = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);

    createBladeProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
        'sku' => 'AUD-HP-001',
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_200, 'sale_price_amount' => 7_999, 'reference' => 'LOT-HP-A'],
        ['qty_on_hand' => 2, 'buy_price_amount' => 4_600, 'sale_price_amount' => 8_999, 'reference' => 'LOT-HP-B'],
        ['qty_on_hand' => 0, 'buy_price_amount' => 5_000, 'sale_price_amount' => 9_999, 'reference' => 'LOT-HP-C'],
    ]);

    $this->get('/products/studio-wireless-headphones')
        ->assertOk()
        ->assertSee('Studio Wireless Headphones')
        ->assertSee('$89.99')
        ->assertDontSee('$99.99')
        ->assertDontSee('$42.00')
        ->assertDontSee('$46.00')
        ->assertDontSee('LOT-HP-A')
        ->assertDontSee('LOT-HP-B')
        ->assertDontSee('LOT-HP-C');
});

it('shows out-of-stock products without a price', function (): void {
    $category = Category::factory()->create();

    createBladeProduct($category, [
        'name' => 'Noise-Isolating Earbuds',
        'slug' => 'noise-isolating-earbuds',
    ], [
        ['qty_on_hand' => 0, 'buy_price_amount' => 3_000, 'sale_price_amount' => 5_499],
    ]);

    $this->get('/products/noise-isolating-earbuds')
        ->assertOk()
        ->assertSee('Out of stock')
        ->assertDontSee('$54.99');
});

it('converts the displayed price with the currency query parameter', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
    ]);

    $category = Category::factory()->create();

    createBladeProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_200, 'sale_price_amount' => 7_999],
    ]);

    $this->get('/products/studio-wireless-headphones?currency=EUR')
        ->assertOk()
        ->assertSee('€73.59')
        ->assertSee('EUR · €');
});

it('remembers the selected currency in the session', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
    ]);

    $category = Category::factory()->create();

    createBladeProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_200, 'sale_price_amount' => 7_999],
    ]);

    $this->withSession(['currency' => 'EUR'])
        ->get('/products/studio-wireless-headphones')
        ->assertOk()
        ->assertSee('€73.59');
});

it('falls back to the default currency for unsupported values', function (): void {
    $category = Category::factory()->create();

    createBladeProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_200, 'sale_price_amount' => 7_999],
    ]);

    $this->get('/products/studio-wireless-headphones?currency=ZZZ')
        ->assertOk()
        ->assertSee('$79.99');
});

it('marks products as unavailable when the selected currency has no rate', function (): void {
    $category = Category::factory()->create();

    createBladeProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_200, 'sale_price_amount' => 7_999],
    ]);

    $this->get('/products/studio-wireless-headphones?currency=GBP')
        ->assertOk()
        ->assertSee('Unavailable in GBP')
        ->assertSee('No GBP price available');
});

it('searches the catalog by term', function (): void {
    $category = Category::factory()->create();

    createBladeProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_200, 'sale_price_amount' => 7_999],
    ]);

    createBladeProduct($category, [
        'name' => 'Ceramic Pour-Over Set',
        'slug' => 'ceramic-pour-over-set',
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 1_800, 'sale_price_amount' => 4_599],
    ]);

    $this->get('/search?q=wireless')
        ->assertOk()
        ->assertSee('Studio Wireless Headphones')
        ->assertDontSee('Ceramic Pour-Over Set');
});

it('lists all products when the search term is empty', function (): void {
    $category = Category::factory()->create();

    createBladeProduct($category, ['name' => 'Studio Headphones', 'slug' => 'studio-headphones'], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_000],
    ]);

    createBladeProduct($category, ['name' => 'Ceramic Set', 'slug' => 'ceramic-set'], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 1_800, 'sale_price_amount' => 4_599],
    ]);

    $this->get('/search')
        ->assertOk()
        ->assertSee('Studio Headphones')
        ->assertSee('Ceramic Set');
});

it('returns 404 for inactive or unknown storefront records', function (): void {
    $category = Category::factory()->create(['slug' => 'audio']);

    createBladeProduct($category, [
        'name' => 'Hidden Product',
        'slug' => 'hidden-product',
        'is_active' => false,
    ], [
        ['qty_on_hand' => 5, 'buy_price_amount' => 4_000, 'sale_price_amount' => 7_000],
    ]);

    Category::factory()->inactive()->create(['slug' => 'hidden-category']);

    $this->get('/products/hidden-product')->assertNotFound();
    $this->get('/products/does-not-exist')->assertNotFound();
    $this->get('/categories/hidden-category')->assertNotFound();
    $this->get('/categories/does-not-exist')->assertNotFound();
});
