<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->optional()->sentence(14),
            'position' => fake()->numberBetween(0, 20),
            'is_active' => true,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function childOf(Category $parent): self
    {
        return $this->state(fn (): array => ['parent_id' => $parent->getKey()]);
    }
}
