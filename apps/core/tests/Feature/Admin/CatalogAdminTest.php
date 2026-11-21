<?php

declare(strict_types=1);

use App\Core\Catalog\Models\Category;
use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Models\Stock;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\StocksRelationManager;
use App\Filament\Widgets\StockValueOverview;
use App\Models\Admin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('redirects guests from the admin panel to the login page', function (): void {
    $this->get('/admin/products')->assertRedirect('/admin/login');
});

it('allows authenticated admins to browse catalog and settings pages', function (): void {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin');

    $this->get('/admin')->assertOk();
    $this->get('/admin/products')->assertOk();
    $this->get('/admin/categories')->assertOk();
    $this->get('/admin/exchange-rates')->assertOk();
});

it('creates a category from the admin panel', function (): void {
    $this->actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Audio',
            'slug' => 'audio',
            'position' => 1,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Audio',
        'slug' => 'audio',
        'is_active' => true,
    ]);
});

it('creates a product from the admin panel', function (): void {
    $category = Category::factory()->create();

    $this->actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->getKey(),
            'name' => 'Studio Wireless Headphones',
            'slug' => 'studio-wireless-headphones',
            'sku' => 'AUD-HP-001',
            'short_description' => 'Closed-back wireless headphones.',
            'is_active' => true,
            'is_featured' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'category_id' => $category->getKey(),
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
        'sku' => 'AUD-HP-001',
        'is_active' => true,
        'is_featured' => true,
    ]);
});

it('adds stock lots with decimal prices through the product relation manager', function (): void {
    $product = Product::factory()->create();

    $this->actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(StocksRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('create', data: [
            'reference' => 'LOT-0001',
            'received_at' => now()->format('Y-m-d H:i:s'),
            'qty_on_hand' => 5,
            'qty_reserved' => 0,
            'buy_price_amount' => '42.00',
            'sale_price_amount' => '79.99',
        ])
        ->assertHasNoTableActionErrors();

    $stock = Stock::query()
        ->where('product_id', $product->getKey())
        ->sole();

    expect($stock->qty_on_hand)->toBe(5)
        ->and($stock->qty_reserved)->toBe(0)
        ->and($stock->buy_price_amount)->toBe(4_200)
        ->and($stock->sale_price_amount)->toBe(7_999)
        ->and($stock->currency)->toBe('USD')
        ->and($stock->reference)->toBe('LOT-0001');

    $this->assertDatabaseHas('stock_movements', [
        'stock_id' => $stock->getKey(),
        'type' => 'purchase',
        'quantity' => 5,
    ]);
});

it('shows derived customer price, available units, and stock value in the product table', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->getKey(),
        'name' => 'Studio Wireless Headphones',
        'slug' => 'studio-wireless-headphones',
    ]);

    Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => 5,
        'qty_reserved' => 0,
        'buy_price_amount' => 4_200,
        'sale_price_amount' => 7_999,
    ]);

    $this->actingAs(Admin::factory()->create(), 'admin');

    $this->get('/admin/products')
        ->assertOk()
        ->assertSee('Studio Wireless Headphones')
        ->assertSee('$79.99')
        ->assertSee('$210.00');
});

it('renders the stock value dashboard widget', function (): void {
    $product = Product::factory()->create();

    Stock::factory()->create([
        'product_id' => $product->getKey(),
        'qty_on_hand' => 5,
        'qty_reserved' => 1,
        'buy_price_amount' => 4_200,
        'sale_price_amount' => 7_999,
    ]);

    $this->actingAs(Admin::factory()->create(), 'admin');

    Livewire::test(StockValueOverview::class)
        ->assertSee('Inventory at a glance')
        ->assertSee('Stock value')
        ->assertSee('$210.00')
        ->assertSee('Units on hand');
});
