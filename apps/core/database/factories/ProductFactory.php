<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Catalog\Models\Category;
use App\Core\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'short_description' => fake()->sentence(12),
            'description' => fake()->paragraphs(3, true),
            'is_active' => true,
            'is_featured' => false,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function featured(): self
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }
}
