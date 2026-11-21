<?php

declare(strict_types=1);

namespace App\Core\Cart\Models;

use App\Core\Support\Database\HasPublicId;
use App\Models\User;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A guest or authenticated shopping cart.
 *
 * The transaction currency is locked when the cart is created. Prices are
 * deliberately not stored on cart rows; they are recalculated from stock lots
 * on every read so the cart always reflects the current derived price.
 *
 * @property int $id
 * @property string $public_id
 * @property int|null $user_id
 * @property string|null $token_hash
 * @property string $currency
 * @property int $version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, CartItem> $items
 * @property-read CartDiscount|null $discount
 * @property-read User|null $user
 */
final class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    use HasPublicId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'token_hash',
        'currency',
        'version',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'token_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('id');
    }

    /**
     * @return HasOne<CartDiscount, $this>
     */
    public function discount(): HasOne
    {
        return $this->hasOne(CartDiscount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /**
     * Bump the optimistic-concurrency version after a mutation.
     */
    public function bumpVersion(): void
    {
        $this->increment('version');
        $this->refresh();
    }
}
