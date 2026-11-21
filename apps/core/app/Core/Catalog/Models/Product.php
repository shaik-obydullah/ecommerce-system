<?php

declare(strict_types=1);

namespace App\Core\Catalog\Models;

use App\Core\Inventory\Models\Stock;
use App\Core\Support\Database\HasPublicId;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A sellable product.
 *
 * Products deliberately contain no price or quantity columns. Those live on
 * stock lots, and the customer-facing price is derived from the lots that are
 * currently in stock.
 *
 * @property int $id
 * @property string $public_id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string $sku
 * @property string|null $short_description
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_featured
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $category
 * @property-read Collection<int, Stock> $stocks
 * @property-read int|null $stocks_count
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Stock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->whereRaw(
                "search_vector @@ websearch_to_tsquery('simple', ?)",
                [$term],
            )->orWhere('name', 'ilike', '%'.$term.'%');
        });
    }

    /**
     * Add derived stock aggregates as select aliases so listings do not need a
     * query per product.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeWithStockSummary(Builder $query): void
    {
        $query->addSelect([
            'derived_sale_price_amount' => Stock::query()
                ->selectRaw('max(stocks.sale_price_amount)')
                ->whereColumn('stocks.product_id', 'products.id')
                ->whereRaw('(stocks.qty_on_hand - stocks.qty_reserved) > 0'),
            'derived_available_qty' => Stock::query()
                ->selectRaw('coalesce(sum(stocks.qty_on_hand - stocks.qty_reserved), 0)')
                ->whereColumn('stocks.product_id', 'products.id')
                ->whereRaw('(stocks.qty_on_hand - stocks.qty_reserved) > 0'),
            'stock_value_amount' => Stock::query()
                ->selectRaw('coalesce(sum(stocks.qty_on_hand * stocks.buy_price_amount), 0)')
                ->whereColumn('stocks.product_id', 'products.id'),
        ]);
    }

    /**
     * Highest sale price among in-stock lots, in the store's base currency.
     */
    public function derivedSalePriceAmount(): ?int
    {
        if (array_key_exists('derived_sale_price_amount', $this->attributes)) {
            $value = $this->attributes['derived_sale_price_amount'];

            return $value === null ? null : (int) $value;
        }

        $amount = $this->stocks()->getQuery()->inStock()->max('sale_price_amount');

        return $amount === null ? null : (int) $amount;
    }

    public function derivedAvailableQty(): int
    {
        if (array_key_exists('derived_available_qty', $this->attributes)) {
            return (int) $this->attributes['derived_available_qty'];
        }

        return (int) $this->stocks()
            ->getQuery()
            ->inStock()
            ->selectRaw('coalesce(sum(qty_on_hand - qty_reserved), 0) as aggregate')
            ->value('aggregate');
    }

    public function inStock(): bool
    {
        return $this->derivedAvailableQty() > 0;
    }

    /**
     * Inventory value of the remaining on-hand units, in the base currency.
     */
    public function stockValueAmount(): int
    {
        if (array_key_exists('stock_value_amount', $this->attributes)) {
            return (int) $this->attributes['stock_value_amount'];
        }

        return (int) $this->stocks()
            ->getQuery()
            ->selectRaw('coalesce(sum(qty_on_hand * buy_price_amount), 0) as aggregate')
            ->value('aggregate');
    }
}
