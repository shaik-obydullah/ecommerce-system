<?php

declare(strict_types=1);

namespace App\Core\Support\Settings;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Cached, typed access to persisted store settings.
 *
 * Settings are read frequently by pricing, checkout, and the storefront, so the
 * full set is cached as one array and invalidated on write.
 */
final class Settings
{
    private const CACHE_KEY = 'ecommerce.settings';

    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        /** @var array<string, mixed> $settings */
        $settings = $this->cache->rememberForever(
            self::CACHE_KEY,
            static fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );

        return $settings;
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group],
        );

        $this->flush();
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();

        $this->flush();
    }

    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
