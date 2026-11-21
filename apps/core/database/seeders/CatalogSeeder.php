<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Catalog\Models\Category;
use App\Core\Catalog\Models\Product;
use App\Core\Support\Money\Currency;
use Illuminate\Database\Seeder;

final class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();

        $this->seedProducts($categories);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $roots = [
            'audio' => [
                'name' => 'Audio',
                'slug' => 'audio',
                'description' => 'Headphones, speakers, and everything that makes a room sound better.',
                'position' => 1,
            ],
            'home' => [
                'name' => 'Home',
                'slug' => 'home',
                'description' => 'Useful, quiet objects for everyday living.',
                'position' => 2,
            ],
            'accessories' => [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'description' => 'Small essentials that travel well.',
                'position' => 3,
            ],
        ];

        $categories = [];

        foreach ($roots as $slug => $data) {
            $categories[$slug] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($data, ['parent_id' => null, 'is_active' => true]),
            );
        }

        $children = [
            'headphones' => ['parent' => 'audio', 'name' => 'Headphones', 'slug' => 'headphones', 'position' => 1],
            'speakers' => ['parent' => 'audio', 'name' => 'Speakers', 'slug' => 'speakers', 'position' => 2],
            'lighting' => ['parent' => 'home', 'name' => 'Lighting', 'slug' => 'lighting', 'position' => 1],
            'textiles' => ['parent' => 'home', 'name' => 'Textiles', 'slug' => 'textiles', 'position' => 2],
        ];

        foreach ($children as $slug => $data) {
            $parent = $categories[$data['parent']];
            unset($data['parent']);

            $categories[$slug] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                array_merge($data, ['parent_id' => $parent->getKey(), 'is_active' => true]),
            );
        }

        return $categories;
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function seedProducts(array $categories): void
    {
        $products = [
            [
                'category' => 'headphones',
                'name' => 'Studio Wireless Headphones',
                'slug' => 'studio-wireless-headphones',
                'sku' => 'AUD-HP-001',
                'short_description' => 'Closed-back wireless headphones with a warm, detailed sound.',
                'description' => 'A comfortable everyday pair with a 40-hour battery, replaceable ear pads, and a folding frame. Tuned for long listening sessions rather than a boosted low end.',
                'is_featured' => true,
                'lots' => [
                    ['qty_on_hand' => 5, 'buy_price_amount' => 4_200, 'sale_price_amount' => 7_999, 'received_at' => now()->subDays(45), 'reference' => 'LOT-HP-A'],
                    ['qty_on_hand' => 3, 'buy_price_amount' => 4_600, 'sale_price_amount' => 8_999, 'received_at' => now()->subDays(20), 'reference' => 'LOT-HP-B'],
                    ['qty_on_hand' => 0, 'buy_price_amount' => 5_000, 'sale_price_amount' => 9_999, 'received_at' => now()->subDays(10), 'reference' => 'LOT-HP-C'],
                ],
            ],
            [
                'category' => 'speakers',
                'name' => 'Everyday Bluetooth Speaker',
                'slug' => 'everyday-bluetooth-speaker',
                'sku' => 'AUD-SP-002',
                'short_description' => 'A compact speaker with a surprisingly full room sound.',
                'description' => 'Splash-resistant, USB-C rechargeable, and small enough for a kitchen shelf. Pairs with a second unit for stereo playback.',
                'is_featured' => true,
                'lots' => [
                    ['qty_on_hand' => 8, 'buy_price_amount' => 3_500, 'sale_price_amount' => 6_499, 'received_at' => now()->subDays(60), 'reference' => 'LOT-SP-A'],
                    ['qty_on_hand' => 4, 'buy_price_amount' => 3_800, 'sale_price_amount' => 6_999, 'received_at' => now()->subDays(15), 'reference' => 'LOT-SP-B'],
                ],
            ],
            [
                'category' => 'accessories',
                'name' => 'USB-C Charging Cable',
                'slug' => 'usb-c-charging-cable',
                'sku' => 'ACC-CB-003',
                'short_description' => 'Braided two-metre cable rated for 100 W charging.',
                'description' => 'A durable everyday cable with reinforced strain relief and a tidy velcro tie.',
                'is_featured' => false,
                'lots' => [
                    ['qty_on_hand' => 40, 'buy_price_amount' => 300, 'sale_price_amount' => 1_299, 'received_at' => now()->subDays(90), 'reference' => 'LOT-CB-A'],
                ],
            ],
            [
                'category' => 'home',
                'name' => 'Ceramic Pour-Over Set',
                'slug' => 'ceramic-pour-over-set',
                'sku' => 'HOM-CF-004',
                'short_description' => 'A hand-glazed dripper and carafe for slow mornings.',
                'description' => 'Matte stoneware with a reusable stainless filter. Dishwasher safe, though it looks better hand-washed.',
                'is_featured' => false,
                'lots' => [
                    ['qty_on_hand' => 6, 'buy_price_amount' => 1_800, 'sale_price_amount' => 4_599, 'received_at' => now()->subDays(35), 'reference' => 'LOT-CF-A'],
                    ['qty_on_hand' => 2, 'buy_price_amount' => 2_000, 'sale_price_amount' => 4_999, 'received_at' => now()->subDays(12), 'reference' => 'LOT-CF-B'],
                ],
            ],
            [
                'category' => 'textiles',
                'name' => 'Linen Throw Blanket',
                'slug' => 'linen-throw-blanket',
                'sku' => 'HOM-TX-005',
                'short_description' => 'Stonewashed linen that gets softer with every wash.',
                'description' => 'A generous throw woven from European flax. Breathable in summer and layer-friendly in winter.',
                'is_featured' => false,
                'lots' => [
                    ['qty_on_hand' => 10, 'buy_price_amount' => 2_500, 'sale_price_amount' => 5_899, 'received_at' => now()->subDays(70), 'reference' => 'LOT-TX-A'],
                ],
            ],
            [
                'category' => 'headphones',
                'name' => 'Noise-Isolating Earbuds',
                'slug' => 'noise-isolating-earbuds',
                'sku' => 'AUD-EB-006',
                'short_description' => 'Small earbuds with passive isolation and a pocket case.',
                'description' => 'Currently between shipments. Add your email in the next phase to be notified when the next lot arrives.',
                'is_featured' => true,
                'lots' => [
                    ['qty_on_hand' => 0, 'buy_price_amount' => 3_000, 'sale_price_amount' => 5_499, 'received_at' => now()->subDays(25), 'reference' => 'LOT-EB-A'],
                ],
            ],
            [
                'category' => 'lighting',
                'name' => 'Arc Desk Lamp',
                'slug' => 'arc-desk-lamp',
                'sku' => 'HOM-LT-007',
                'short_description' => 'A warm, dimmable task light with a slim steel arm.',
                'description' => 'Three colour temperatures and a memory dimmer. The weighted base keeps the arm steady without a clamp.',
                'is_featured' => true,
                'lots' => [
                    ['qty_on_hand' => 7, 'buy_price_amount' => 3_200, 'sale_price_amount' => 7_499, 'received_at' => now()->subDays(28), 'reference' => 'LOT-LT-A'],
                ],
            ],
            [
                'category' => 'accessories',
                'name' => 'Universal Travel Adapter',
                'slug' => 'universal-travel-adapter',
                'sku' => 'ACC-TR-008',
                'short_description' => 'One adapter for most sockets, with two USB-C ports.',
                'description' => 'Compact, fused, and rated for laptops and cameras. Includes a travel pouch.',
                'is_featured' => false,
                'lots' => [
                    ['qty_on_hand' => 15, 'buy_price_amount' => 1_200, 'sale_price_amount' => 2_999, 'received_at' => now()->subDays(55), 'reference' => 'LOT-TR-A'],
                ],
            ],
            [
                'category' => 'speakers',
                'name' => 'Vinyl Record Player',
                'slug' => 'vinyl-record-player',
                'sku' => 'AUD-RP-009',
                'short_description' => 'A belt-driven turntable with a built-in preamp.',
                'description' => 'A simple, serviceable deck with a replaceable cartridge and a dust cover. Ships with a felt mat and RCA cable.',
                'is_featured' => false,
                'lots' => [
                    ['qty_on_hand' => 3, 'buy_price_amount' => 9_000, 'sale_price_amount' => 18_999, 'received_at' => now()->subDays(50), 'reference' => 'LOT-RP-A'],
                    ['qty_on_hand' => 1, 'buy_price_amount' => 9_500, 'sale_price_amount' => 19_999, 'received_at' => now()->subDays(8), 'reference' => 'LOT-RP-B'],
                ],
            ],
            [
                'category' => 'accessories',
                'name' => 'Canvas Market Tote',
                'slug' => 'canvas-market-tote',
                'sku' => 'ACC-TT-010',
                'short_description' => 'Heavy cotton canvas with an inside pocket.',
                'description' => 'A sturdy everyday tote with reinforced handles and a flat base. Fits a laptop and a week of groceries.',
                'is_featured' => false,
                'lots' => [
                    ['qty_on_hand' => 20, 'buy_price_amount' => 800, 'sale_price_amount' => 2_499, 'received_at' => now()->subDays(80), 'reference' => 'LOT-TT-A'],
                ],
            ],
        ];

        foreach ($products as $data) {
            $category = $categories[$data['category']];
            $lots = $data['lots'];
            unset($data['category'], $data['lots']);

            $product = Product::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'category_id' => $category->getKey(),
                    'is_active' => true,
                ]),
            );

            if ($product->stocks()->exists()) {
                continue;
            }

            foreach ($lots as $lot) {
                $product->stocks()->create(array_merge($lot, [
                    'qty_reserved' => 0,
                    'currency' => Currency::base()->code(),
                    'metadata' => null,
                ]));
            }
        }
    }
}
