<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Promotions\Enums\DiscountType;
use App\Core\Promotions\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Discount>
 */
final class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->bothify('SAVE-####-????')),
            'name' => fake()->sentence(3),
            'type' => DiscountType::Percentage,
            'value' => 1_000,
            'currency' => null,
            'min_subtotal_amount' => null,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'per_user_limit' => null,
        ];
    }

    public function percentage(int $basisPoints): self
    {
        return $this->state(fn (): array => [
            'type' => DiscountType::Percentage,
            'value' => $basisPoints,
            'currency' => null,
        ]);
    }

    public function fixed(int $amount, string $currency = 'USD'): self
    {
        return $this->state(fn (): array => [
            'type' => DiscountType::Fixed,
            'value' => $amount,
            'currency' => $currency,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['ends_at' => now()->subDay()]);
    }
}
