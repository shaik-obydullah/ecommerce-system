<?php

declare(strict_types=1);

namespace App\Core\Promotions\Models;

use App\Core\Promotions\Enums\DiscountType;
use App\Core\Support\Database\HasPublicId;
use App\Core\Support\Money\Currency;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A coupon definition.
 *
 * Coupons are validated and priced server-side; clients only submit the code.
 * Usage is recorded in `discount_usages` when an order is placed.
 *
 * @property int $id
 * @property string $public_id
 * @property string $code
 * @property string $name
 * @property DiscountType $type
 * @property int $value
 * @property string|null $currency
 * @property int|null $min_subtotal_amount
 * @property bool $is_active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property int|null $per_user_limit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DiscountUsage> $usages
 */
final class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    use HasPublicId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'currency',
        'min_subtotal_amount',
        'is_active',
        'starts_at',
        'ends_at',
        'usage_limit',
        'usage_count',
        'per_user_limit',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'integer',
            'min_subtotal_amount' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'per_user_limit' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::saving(function (self $discount): void {
            $discount->code = Str::upper(trim($discount->code));
            $discount->currency = $discount->currency === null
                ? null
                : Str::upper(trim($discount->currency));
        });
    }

    /**
     * @return HasMany<DiscountUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function isWithinWindow(?Carbon $at = null): bool
    {
        $at ??= now();

        if ($this->starts_at !== null && $this->starts_at->greaterThan($at)) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->greaterThanOrEqualTo($at);
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit !== null && $this->usage_count >= $this->usage_limit;
    }

    public function isAvailableInCurrency(Currency $currency): bool
    {
        return $this->type->isPercentage()
            || ($this->currency !== null && $this->currency === $currency->code());
    }
}
