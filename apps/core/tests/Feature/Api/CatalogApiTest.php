<?php

declare(strict_types=1);

use App\Core\Catalog\Models\Category;
use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Models\Stock;
use App\Core\Pricing\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function createApiProduct(Category $category, array $attributes, int $salePrice, int $quantity = 5): Product
{
    $product = Product::factory()->create(array_merge([
        'category_id' => $category->getKey(),
        'is_active' => true,
        'is_featured' => false,
    ], $attributes));

    Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => $quantity,
        'qty_reserved' => 0,
        'buy_price_amount' => max(1, (int) ($salePrice / 2)),
        'sale_price_amount' => $salePrice,
    ]);

    return $product;
}

it('lists active products with one derived price and no stock internals', function (): void {
    $category = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);

    createApiProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
    ], 7_999);

    createApiProduct($category, [
        'name' => 'Bluetooth Speaker',
        'slug' => 'bluetooth-speaker',
        'is_featured' => true,
    ], 6_499);

    createApiProduct($category, [
        'name' => 'Discontinued Earbuds',
        'slug' => 'discontinued-earbuds',
    ], 9_999, 0);

    createApiProduct($category, [
        'name' => 'Hidden Product',
        'slug' => 'hidden-product',
        'is_active' => false,
    ], 1_000);

    $response = $this->getJson('/api/v1/products')->assertOk();

    $products = collect($response->json('data'));

    expect($products)->toHaveCount(3)
        ->and($products->pluck('slug'))->not->toContain('hidden-product');

    $headphones = $products->firstWhere('slug', 'studio-wireless-headphones');

    expect($headphones['price']['amount'])->toBe(7_999)
        ->and($headphones['price']['currency'])->toBe('USD')
        ->and($headphones['price']['formatted'])->toBe('$79.99')
        ->and($headphones['in_stock'])->toBeTrue()
        ->and($headphones['is_purchasable'])->toBeTrue()
        ->and($headphones['category'])->toMatchArray([
            'name' => 'Audio',
            'slug' => 'audio',
        ])
        ->and($headphones)->not->toHaveKey('stocks')
        ->and($headphones)->not->toHaveKey('buy_price_amount')
        ->and($headphones)->not->toHaveKey('sale_price_amount')
        ->and($headphones)->not->toHaveKey('stock_value_amount');

    $earbuds = $products->firstWhere('slug', 'discontinued-earbuds');

    expect($earbuds['price'])->toBeNull()
        ->and($earbuds['in_stock'])->toBeFalse()
        ->and($earbuds['is_purchasable'])->toBeFalse();
});

it('shows the highest in-stock sale price and ignores out-of-stock lots', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->getKey(),
        'slug' => 'multi-lot-product',
    ]);

    Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => 5,
        'buy_price_amount' => 4_000,
        'sale_price_amount' => 7_000,
    ]);

    Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => 2,
        'buy_price_amount' => 4_500,
        'sale_price_amount' => 8_000,
    ]);

    Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => 0,
        'buy_price_amount' => 5_000,
        'sale_price_amount' => 9_900,
    ]);

    $this->getJson('/api/v1/products/multi-lot-product')
        ->assertOk()
        ->assertJsonPath('data.price.amount', 8_000)
        ->assertJsonPath('data.in_stock', true)
        ->assertJsonPath('data.is_purchasable', true)
        ->assertJsonMissingPath('data.stocks');
});

it('filters products by category', function (): void {
    $audio = Category::factory()->create(['slug' => 'audio']);
    $home = Category::factory()->create(['slug' => 'home']);

    createApiProduct($audio, ['name' => 'Headphones', 'slug' => 'headphones'], 7_000);
    createApiProduct($home, ['name' => 'Lamp', 'slug' => 'lamp'], 5_000);

    $response = $this->getJson('/api/v1/products?category=audio')->assertOk();

    expect(collect($response->json('data'))->pluck('slug')->all())->toBe(['headphones']);
});

it('filters featured products', function (): void {
    $category = Category::factory()->create();

    createApiProduct($category, ['name' => 'Featured', 'slug' => 'featured', 'is_featured' => true], 7_000);
    createApiProduct($category, ['name' => 'Regular', 'slug' => 'regular'], 5_000);

    $response = $this->getJson('/api/v1/products?featured=1')->assertOk();

    expect(collect($response->json('data'))->pluck('slug')->all())->toBe(['featured']);
});

it('filters to in-stock products', function (): void {
    $category = Category::factory()->create();

    createApiProduct($category, ['name' => 'Available', 'slug' => 'available'], 7_000);
    createApiProduct($category, ['name' => 'Sold Out', 'slug' => 'sold-out'], 9_000, 0);

    $response = $this->getJson('/api/v1/products?in_stock=1')->assertOk();

    expect(collect($response->json('data'))->pluck('slug')->all())->toBe(['available']);
});

it('searches products by name and description', function (): void {
    $category = Category::factory()->create();

    createApiProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
    ], 7_000);

    createApiProduct($category, [
        'name' => 'Ceramic Pour-Over Set',
        'slug' => 'ceramic-pour-over-set',
        'description' => 'A hand-glazed dripper for slow mornings.',
    ], 4_000);

    $response = $this->getJson('/api/v1/products?q=wireless')->assertOk();

    expect(collect($response->json('data'))->pluck('slug')->all())
        ->toBe(['studio-wireless-headphones']);
});

it('sorts products by price in both directions', function (): void {
    $category = Category::factory()->create();

    createApiProduct($category, ['name' => 'Mid', 'slug' => 'mid'], 5_000);
    createApiProduct($category, ['name' => 'Low', 'slug' => 'low'], 1_000);
    createApiProduct($category, ['name' => 'High', 'slug' => 'high'], 9_000);
    createApiProduct($category, ['name' => 'Sold Out', 'slug' => 'sold-out'], 12_000, 0);

    $ascending = $this->getJson('/api/v1/products?sort=price_asc')->assertOk();
    $descending = $this->getJson('/api/v1/products?sort=price_desc')->assertOk();

    expect(collect($ascending->json('data'))->pluck('slug')->all())
        ->toBe(['low', 'mid', 'high', 'sold-out'])
        ->and(collect($descending->json('data'))->pluck('slug')->all())
        ->toBe(['high', 'mid', 'low', 'sold-out']);
});

it('sorts products by name', function (): void {
    $category = Category::factory()->create();

    createApiProduct($category, ['name' => 'Zebra', 'slug' => 'zebra'], 5_000);
    createApiProduct($category, ['name' => 'Apple', 'slug' => 'apple'], 1_000);

    $response = $this->getJson('/api/v1/products?sort=name')->assertOk();

    expect(collect($response->json('data'))->pluck('slug')->all())->toBe(['apple', 'zebra']);
});

it('paginates the product list', function (): void {
    $category = Category::factory()->create();

    createApiProduct($category, ['name' => 'One', 'slug' => 'one'], 1_000);
    createApiProduct($category, ['name' => 'Two', 'slug' => 'two'], 2_000);
    createApiProduct($category, ['name' => 'Three', 'slug' => 'three'], 3_000);

    $this->getJson('/api/v1/products?per_page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonStructure(['links' => ['first', 'last', 'prev', 'next']]);
});

it('converts prices into the requested currency', function (): void {
    ExchangeRate::factory()->create([
        'base_currency' => 'USD',
        'quote_currency' => 'EUR',
        'rate' => '0.9200000000',
    ]);

    $category = Category::factory()->create();

    createApiProduct($category, ['name' => 'Headphones', 'slug' => 'headphones'], 7_999);

    $this->getJson('/api/v1/products?currency=EUR')
        ->assertOk()
        ->assertJsonPath('data.0.price.amount', 7_359)
        ->assertJsonPath('data.0.price.currency', 'EUR');
});

it('marks products as not purchasable when the requested currency has no rate', function (): void {
    $category = Category::factory()->create();

    createApiProduct($category, ['name' => 'Headphones', 'slug' => 'headphones'], 7_999);

    $this->getJson('/api/v1/products?currency=GBP')
        ->assertOk()
        ->assertJsonPath('data.0.price', null)
        ->assertJsonPath('data.0.in_stock', true)
        ->assertJsonPath('data.0.is_purchasable', false);
});

it('rejects unsupported currencies with problem details', function (): void {
    $this->getJson('/api/v1/products?currency=ZZZ')
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 422)
        ->assertJsonStructure(['errors' => ['currency']]);
});

it('rejects invalid catalog filters', function (string $query): void {
    $this->getJson('/api/v1/products?'.$query)
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/problem+json');
})->with([
    'unknown sort' => 'sort=cheapest',
    'per page too large' => 'per_page=101',
    'per page zero' => 'per_page=0',
]);

it('returns a product by slug', function (): void {
    $category = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);

    createApiProduct($category, [
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
        'sku' => 'AUD-HP-001',
    ], 7_999);

    $this->getJson('/api/v1/products/studio-wireless-headphones')
        ->assertOk()
        ->assertJsonPath('data.slug', 'studio-wireless-headphones')
        ->assertJsonPath('data.sku', 'AUD-HP-001')
        ->assertJsonPath('data.category.slug', 'audio')
        ->assertJsonPath('data.price.amount', 7_999)
        ->assertJsonMissingPath('data.stocks');
});

it('returns 404 for inactive or unknown products', function (): void {
    $category = Category::factory()->create();

    createApiProduct($category, [
        'name' => 'Hidden',
        'slug' => 'hidden',
        'is_active' => false,
    ], 7_000);

    $this->getJson('/api/v1/products/hidden')->assertNotFound();
    $this->getJson('/api/v1/products/does-not-exist')->assertNotFound();
});

it('lists active categories with product counts', function (): void {
    $audio = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);
    Category::factory()->inactive()->create(['name' => 'Hidden', 'slug' => 'hidden']);
    createApiProduct($audio, ['name' => 'Headphones', 'slug' => 'headphones'], 7_000);

    $response = $this->getJson('/api/v1/categories')->assertOk();

    $categories = collect($response->json('data'));

    expect($categories->pluck('slug'))->toContain('audio')
        ->and($categories->pluck('slug'))->not->toContain('hidden')
        ->and($categories->firstWhere('slug', 'audio')['product_count'])->toBe(1);
});

it('shows a category with its active children', function (): void {
    $audio = Category::factory()->create(['name' => 'Audio', 'slug' => 'audio']);
    Category::factory()->childOf($audio)->create(['name' => 'Headphones', 'slug' => 'headphones']);
    Category::factory()->childOf($audio)->inactive()->create(['name' => 'Retired', 'slug' => 'retired']);

    $response = $this->getJson('/api/v1/categories/audio')->assertOk();

    expect($response->json('data.slug'))->toBe('audio')
        ->and(collect($response->json('data.children'))->pluck('slug')->all())
        ->toBe(['headphones']);
});

it('returns 404 for inactive or unknown categories', function (): void {
    Category::factory()->inactive()->create(['slug' => 'hidden']);

    $this->getJson('/api/v1/categories/hidden')->assertNotFound();
    $this->getJson('/api/v1/categories/does-not-exist')->assertNotFound();
});
