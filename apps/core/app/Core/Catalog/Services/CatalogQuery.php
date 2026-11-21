<?php

declare(strict_types=1);

namespace App\Core\Catalog\Services;

use App\Core\Catalog\Models\Product;
use App\Core\Support\Settings\Settings;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The single catalog query used by the API and the Blade storefront.
 *
 * Filters are whitelisted here as a second line of defence even when callers
 * already validate them.
 */
final class CatalogQuery
{
    private const SORTS = ['newest', 'name', 'price_asc', 'price_desc'];

    public function __construct(
        private readonly Settings $settings,
    ) {}

    /**
     * @param  array{category?: string|null, q?: string|null, featured?: bool|null, in_stock?: bool|null, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function products(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $query = Product::query()
            ->active()
            ->with(['category' => fn (BelongsTo $query) => $query->select('id', 'public_id', 'name', 'slug')])
            ->withStockSummary();

        $category = $filters['category'] ?? null;

        if (is_string($category) && $category !== '') {
            $query->whereHas(
                'category',
                fn ($query) => $query->active()->where('slug', $category),
            );
        }

        $term = $filters['q'] ?? null;

        if (is_string($term) && trim($term) !== '') {
            $query->search($term);
        }

        if (($filters['featured'] ?? false) === true) {
            $query->featured();
        }

        if (($filters['in_stock'] ?? false) === true) {
            $query->whereHas('stocks', fn ($query) => $query->inStock());
        }

        $sort = $filters['sort'] ?? 'newest';

        if (! is_string($sort) || ! in_array($sort, self::SORTS, true)) {
            $sort = 'newest';
        }

        match ($sort) {
            'name' => $query->orderBy('products.name'),
            'price_asc' => $query->orderByRaw($this->priceOrderSql().' asc nulls last'),
            'price_desc' => $query->orderByRaw($this->priceOrderSql().' desc nulls last'),
            default => $query->orderByDesc('products.created_at'),
        };

        $query->orderBy('products.id');

        $perPage ??= (int) $this->settings->get('catalog.products_per_page', 24);
        $perPage = max(1, min(100, $perPage));

        return $query->paginate($perPage)->withQueryString();
    }

    private function priceOrderSql(): string
    {
        return '(select max(stocks.sale_price_amount) from stocks '
            .'where stocks.product_id = products.id '
            .'and (stocks.qty_on_hand - stocks.qty_reserved) > 0 '
            .'and stocks.deleted_at is null)';
    }
}
