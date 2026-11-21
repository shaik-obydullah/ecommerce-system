<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * Admins authenticate through the separate "admin" guard, so check that
     * guard rather than the default storefront guard. Local environments are
     * already unrestricted by Horizon itself.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null): bool {
            return auth('admin')->check();
        });
    }
}
