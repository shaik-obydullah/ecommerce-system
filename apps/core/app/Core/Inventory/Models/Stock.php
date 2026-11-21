<?php

declare(strict_types=1);

namespace App\Core\Inventory\Models;

use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Enums\StockMovementType;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;
use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A physical stock lot for a product.
 *
 * Only stock rows own quantity and prices. The storefront derives a single
 * price from the lots that are currently available.
 *
 * @property int $id
 * @property int $product_id
 * @property int $qty_on_hand
 * @property int $qty_reserved
 * @property int $buy_price_amount
 * @property int $sale_price_amount
 * @property string $currency
 * @property Carbon|null $received_at
 * @property string|null $reference
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Product $product
 * @property-read Collection<int, StockMovement> $movements
 */
final class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'qty_on_hand',
        'qty_reserved',
        'buy_price_amount',
        'sale_price_amount',
        'currency',
        'received_at',
        'reference',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'integer',
            'qty_reserved' => 'integer',
            'buy_price_amount' => 'integer',
            'sale_price_amount' => 'integer',
            'received_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        self::created(function (self $stock): void {
            if ($stock->qty_on_hand > 0) {
                $stock->recordMovement(
                    type: StockMovementType::Purchase,
                    quantity: $stock->qty_on_hand,
                );
            }
        });

        self::updated(function (self $stock): void {
            if ($stock->wasChanged('qty_on_hand')) {
                $delta = $stock->qty_on_hand - (int) $stock->getOriginal('qty_on_hand');

                if ($delta !== 0) {
                    $stock->recordMovement(
                        type: StockMovementType::Adjustment,
                        quantity: $delta,
                    );
                }
            }

            if ($stock->wasChanged(['buy_price_amount', 'sale_price_amount'])) {
                $stock->recordMovement(
                    type: StockMovementType::PriceChange,
                    quantity: 0,
                    metadata: [
                        'previous_buy_price_amount' => (int) $stock->getOriginal('buy_price_amount'),
                        'previous_sale_price_amount' => (int) $stock->getOriginal('sale_price_amount'),
                    ],
                );
            }
        });

        self::deleted(function (self $stock): void {
            if ($stock->qty_on_hand > 0) {
                $stock->recordMovement(
                    type: StockMovementType::Adjustment,
                    quantity: -$stock->qty_on_hand,
                    metadata: ['reason' => 'stock lot deleted'],
                );
            }
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeInStock(Builder $query): void
    {
        $query->whereRaw('(qty_on_hand - qty_reserved) > 0');
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->inStock();
    }

    public function qtyAvailable(): int
    {
        return $this->qty_on_hand - $this->qty_reserved;
    }

    public function buyPrice(): Money
    {
        return Money::of($this->buy_price_amount, $this->currency);
    }

    public function salePrice(): Money
    {
        return Money::of($this->sale_price_amount, $this->currency);
    }

    /**
     * Inventory value of this lot at its recorded buy price.
     */
    public function stockValueAmount(): int
    {
        return $this->qty_on_hand * $this->buy_price_amount;
    }

    public function stockValue(): Money
    {
        return Money::of($this->stockValueAmount(), Currency::base());
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function recordMovement(
        StockMovementType $type,
        int $quantity,
        ?array $metadata = null,
    ): void {
        $this->movements()->create([
            'product_id' => $this->product_id,
            'type' => $type,
            'quantity' => $quantity,
            'unit_buy_price_amount' => $this->buy_price_amount,
            'unit_sale_price_amount' => $this->sale_price_amount,
            'currency' => $this->currency,
            'reference' => $this->reference,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
