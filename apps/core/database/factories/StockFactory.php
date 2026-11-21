<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Models\Stock;
use App\Core\Support\Money\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
final class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buyPrice = fake()->numberBetween(500, 5_000);

        return [
            'product_id' => Product::factory(),
            'qty_on_hand' => fake()->numberBetween(0, 50),
            'qty_reserved' => 0,
            'buy_price_amount' => $buyPrice,
            'sale_price_amount' => $buyPrice + fake()->numberBetween(500, 5_000),
            'currency' => Currency::base()->code(),
            'received_at' => fake()->dateTimeBetween('-6 months'),
            'reference' => fake()->optional()->bothify('LOT-####'),
            'metadata' => null,
        ];
    }

    public function outOfStock(): self
    {
        return $this->state(fn (): array => ['qty_on_hand' => 0, 'qty_reserved' => 0]);
    }

    public function withQuantity(int $quantity): self
    {
        return $this->state(fn (): array => ['qty_on_hand' => $quantity]);
    }
}
