<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Cart\Models\Cart;
use App\Core\Cart\Models\CartItem;
use App\Core\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
final class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 3),
        ];
    }
}
