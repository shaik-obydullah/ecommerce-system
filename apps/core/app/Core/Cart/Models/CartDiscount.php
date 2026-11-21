<?php

declare(strict_types=1);

namespace App\Core\Cart\Models;

use App\Core\Promotions\Models\Discount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The single coupon currently attached to a cart.
 *
 * @property int $id
 * @property int $cart_id
 * @property int $discount_id
 * @property string $code
 * @property-read Cart $cart
 * @property-read Discount $discount
 */
final class CartDiscount extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'discount_id',
        'code',
    ];

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<Discount, $this>
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
