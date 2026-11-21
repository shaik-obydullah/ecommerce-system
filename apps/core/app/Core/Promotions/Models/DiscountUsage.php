<?php

declare(strict_types=1);

namespace App\Core\Promotions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A recorded coupon redemption. Written when an order is placed, so the same
 * coupon cannot be reused beyond its limits.
 *
 * @property int $id
 * @property int $discount_id
 * @property int|null $user_id
 * @property int|null $cart_id
 * @property string|null $email
 * @property int $amount
 * @property string $currency
 * @property int|null $order_id
 * @property Carbon $used_at
 */
final class DiscountUsage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'discount_id',
        'user_id',
        'cart_id',
        'email',
        'amount',
        'currency',
        'order_id',
        'used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Discount, $this>
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
