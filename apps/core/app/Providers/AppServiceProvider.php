<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Support\Settings\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            Settings::class,
            fn ($app): Settings => new Settings($app->make('cache.store')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Domain models live under App\Core\*, so the default factory resolver
        // would look for Database\Factories\Core\...\*Factory. All factories are
        // flat in Database\Factories, keyed by the model basename.
        Factory::guessFactoryNamesUsing(
            static fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory',
        );

        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute((int) config('ecommerce.api.rate_limit_per_minute', 60))
                ->by((string) $key);
        });

        RateLimiter::for('cart', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute((int) config('ecommerce.cart.rate_limit_per_minute', 30))
                ->by((string) $key);
        });
    }
}
