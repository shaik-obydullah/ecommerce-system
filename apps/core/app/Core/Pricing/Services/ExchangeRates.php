<?php

declare(strict_types=1);

namespace App\Core\Pricing\Services;

use App\Core\Pricing\Models\ExchangeRate;
use App\Core\Support\Money\Currency;
use App\Core\Support\Money\Money;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Resolves FX rates and converts money without floating-point arithmetic.
 *
 * A direct rate is preferred. If only the inverse pair exists, its reciprocal
 * is used. If neither exists the conversion returns null and the caller must
 * treat the product as unavailable rather than silently using another currency.
 */
final class ExchangeRates
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function rate(string $from, string $to): ?string
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === $to) {
            return '1';
        }

        $cacheKey = $this->cacheKey($from, $to);
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return (string) $cached;
        }

        $direct = $this->latestRate($from, $to);

        if ($direct !== null) {
            $this->cache->put($cacheKey, $direct, $this->ttl());

            return $direct;
        }

        $inverse = $this->latestRate($to, $from);

        if ($inverse !== null && bccomp($inverse, '0', 12) === 1) {
            $computed = bcdiv('1', $inverse, 12);
            $this->cache->put($cacheKey, $computed, $this->ttl());

            return $computed;
        }

        return null;
    }

    public function convert(Money $money, Currency|string $to): ?Money
    {
        $target = $to instanceof Currency ? $to : Currency::fromCode($to);

        if ($money->currency()->equals($target)) {
            return $money;
        }

        $rate = $this->rate($money->currency()->code(), $target->code());

        if ($rate === null) {
            return null;
        }

        $targetMajor = bcmul($money->toDecimalString(), $rate, 12);

        return Money::fromDecimal($targetMajor, $target);
    }

    public function forget(string $from, string $to): void
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        $this->cache->forget($this->cacheKey($from, $to));
        $this->cache->forget($this->cacheKey($to, $from));
    }

    private function latestRate(string $from, string $to): ?string
    {
        $rate = ExchangeRate::query()
            ->pair($from, $to)
            ->latestFirst()
            ->value('rate');

        return $rate === null ? null : (string) $rate;
    }

    private function cacheKey(string $from, string $to): string
    {
        return "ecommerce.fx.{$from}.{$to}";
    }

    private function ttl(): int
    {
        return (int) config('ecommerce.fx.cache_ttl', 3600);
    }
}
