<?php

declare(strict_types=1);

namespace App\Core\Inventory\Models;

use App\Core\Catalog\Models\Product;
use App\Core\Inventory\Enums\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Immutable audit trail for every stock change.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $stock_id
 * @property StockMovementType $type
 * @property int $quantity
 * @property int $unit_buy_price_amount
 * @property int $unit_sale_price_amount
 * @property string $currency
 * @property string|null $reference
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read Stock|null $stock
 */
final class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'stock_id',
        'type',
        'quantity',
        'unit_buy_price_amount',
        'unit_sale_price_amount',
        'currency',
        'reference',
        'metadata',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'unit_buy_price_amount' => 'integer',
            'unit_sale_price_amount' => 'integer',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Stock, $this>
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
