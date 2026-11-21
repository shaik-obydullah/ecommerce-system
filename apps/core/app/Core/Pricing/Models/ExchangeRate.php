<?php

declare(strict_types=1);

namespace App\Core\Pricing\Models;

use App\Core\Pricing\Services\ExchangeRates;
use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A dated FX rate between two ISO 4217 currencies.
 *
 * The latest row for a pair is authoritative. Rates are stored as exact
 * decimals and converted with bcmath, never floats.
 *
 * @property int $id
 * @property string $base_currency
 * @property string $quote_currency
 * @property string $rate
 * @property string|null $source
 * @property Carbon|null $fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'source',
        'fetched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:10',
            'fetched_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::saved(function (self $rate): void {
            app(ExchangeRates::class)->forget($rate->base_currency, $rate->quote_currency);
        });

        self::deleted(function (self $rate): void {
            app(ExchangeRates::class)->forget($rate->base_currency, $rate->quote_currency);
        });
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePair(Builder $query, string $base, string $quote): void
    {
        $query->where('base_currency', strtoupper($base))
            ->where('quote_currency', strtoupper($quote));
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('fetched_at')->orderByDesc('id');
    }
}
