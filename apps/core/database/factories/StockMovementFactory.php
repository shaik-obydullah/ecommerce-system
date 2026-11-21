<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Inventory\Enums\StockMovementType;
use App\Core\Inventory\Models\Stock;
use App\Core\Inventory\Models\StockMovement;
use App\Core\Support\Money\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
final class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_id' => Stock::factory(),
            'product_id' => fn (array $attributes): int => Stock::query()
                ->findOrFail($attributes['stock_id'])
                ->product_id,
            'type' => StockMovementType::Adjustment,
            'quantity' => fake()->numberBetween(-10, 10),
            'unit_buy_price_amount' => fake()->numberBetween(500, 5_000),
            'unit_sale_price_amount' => fake()->numberBetween(5_500, 10_000),
            'currency' => Currency::base()->code(),
            'reference' => fake()->optional()->bothify('LOT-####'),
            'metadata' => null,
            'occurred_at' => now(),
        ];
    }
}
