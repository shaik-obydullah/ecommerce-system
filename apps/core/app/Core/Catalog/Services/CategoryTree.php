<?php

declare(strict_types=1);

namespace App\Core\Catalog\Services;

use App\Core\Catalog\Models\Category;
use App\Core\Catalog\Support\CatalogCache;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds the cached, active category tree used by storefront navigation.
 */
final class CategoryTree
{
    public function __construct(
        private readonly CatalogCache $cache,
    ) {}

    /**
     * @return list<array{public_id: string, name: string, slug: string, product_count: int, children: list<array<string, mixed>>}>
     */
    public function activeTree(): array
    {
        return $this->cache->rememberCategoryTree(function (): array {
            /** @var Collection<int, Category> $categories */
            $categories = Category::query()
                ->active()
                ->ordered()
                ->withCount(['products' => fn ($query) => $query->active()])
                ->get(['id', 'public_id', 'parent_id', 'name', 'slug']);

            return $this->build($categories, null);
        });
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return list<array{public_id: string, name: string, slug: string, product_count: int, children: list<array<string, mixed>>}>
     */
    private function build(Collection $categories, ?int $parentId): array
    {
        return $categories
            ->filter(function (Category $category) use ($parentId): bool {
                $categoryParent = $category->parent_id === null ? null : (int) $category->parent_id;

                return $categoryParent === $parentId;
            })
            ->map(fn (Category $category): array => [
                'public_id' => (string) $category->public_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'product_count' => (int) ($category->products_count ?? 0),
                'children' => $this->build($categories, $category->id),
            ])
            ->values()
            ->all();
    }
}
