<?php

declare(strict_types=1);

namespace App\Core\Catalog\Support;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Cache keys shared by the catalog storefront and API.
 */
final class CatalogCache
{
    private const CATEGORY_TREE_KEY = 'ecommerce.catalog.category_tree';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    /**
     * @param  Closure(): array<int, array<string, mixed>>  $callback
     * @return array<int, array<string, mixed>>
     */
    public function rememberCategoryTree(Closure $callback): array
    {
        /** @var array<int, array<string, mixed>> $tree */
        $tree = $this->cache->remember(
            self::CATEGORY_TREE_KEY,
            now()->addHour(),
            $callback,
        );

        return $tree;
    }

    public function forgetCategoryTree(): void
    {
        $this->cache->forget(self::CATEGORY_TREE_KEY);
    }
}
