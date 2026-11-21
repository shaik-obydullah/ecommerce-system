<?php

declare(strict_types=1);

namespace App\Core\Cart\Models;

use App\Core\Catalog\Models\Product;
use App\Core\Support\Database\HasPublicId;
use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A product line in a cart. Quantity is validated against available stock on
 * every mutation; the price is always derived, never stored.
 *
 * @property int $id
 * @property string $public_id
 * @property int $cart_id
 * @property int $product_id
 * @property int $quantity
 * @property-read Cart $cart
 * @property-read Product $product
 */
final class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    use HasPublicId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
